<?php
declare(strict_types=1);

final class RegistrationExportException extends RuntimeException
{
}

function append_student_registration_exports(array $registration): void
{
    $configuredDirectory = getenv('STUDENTHUB_REGISTRATION_EXPORT_DIR');
    $directory = $configuredDirectory !== false && trim($configuredDirectory) !== ''
        ? trim($configuredDirectory)
        : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'student-hub-private';

    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RegistrationExportException('Unable to create the private registration export directory.');
    }

    $jsonPath = $directory . DIRECTORY_SEPARATOR . 'student-registrations.json';
    $csvPath = $directory . DIRECTORY_SEPARATOR . 'student-registrations.csv';
    $lockPath = $directory . DIRECTORY_SEPARATOR . 'student-registrations.lock';
    $headers = [
        'student_code',
        'full_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'program',
        'academic_year',
        'registered_at',
    ];
    $registration = array_intersect_key($registration, array_fill_keys($headers, true));
    if (count($registration) !== count($headers)) {
        throw new RegistrationExportException('The registration export record is missing required fields.');
    }

    $lock = fopen($lockPath, 'c+b');
    if ($lock === false) {
        throw new RegistrationExportException('Unable to open the registration export lock.');
    }

    $temporaryPaths = [];
    $backupPaths = [];
    $installedPaths = [];
    try {
        if (!flock($lock, LOCK_EX)) {
            throw new RegistrationExportException('Unable to lock the registration exports.');
        }

        $existingJson = is_file($jsonPath) ? file_get_contents($jsonPath) : false;
        if ($existingJson === false && is_file($jsonPath)) {
            throw new RegistrationExportException('Unable to read the JSON registration export.');
        }
        $records = $existingJson === false || trim($existingJson) === ''
            ? []
            : json_decode($existingJson, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($records) || !array_is_list($records)) {
            throw new RegistrationExportException('The JSON registration export has an invalid format.');
        }
        foreach ($records as $existingRecord) {
            if (!is_array($existingRecord) || !isset($existingRecord['student_code'])) {
                throw new RegistrationExportException('The JSON registration export contains an invalid record.');
            }
            if ($existingRecord['student_code'] === $registration['student_code']) {
                throw new RegistrationExportException('This student is already present in the JSON registration export.');
            }
        }

        $existingCsv = is_file($csvPath) ? file_get_contents($csvPath) : false;
        if ($existingCsv === false && is_file($csvPath)) {
            throw new RegistrationExportException('Unable to read the CSV registration export.');
        }
        $csvContent = $existingCsv === false ? '' : $existingCsv;
        if ($csvContent !== '') {
            $firstLine = strtok($csvContent, "\r\n");
            if (!is_string($firstLine) || str_getcsv($firstLine, ',', '"', '\\') !== $headers) {
                throw new RegistrationExportException('The CSV registration export has an unexpected header.');
            }
        }

        $csvRow = [];
        foreach ($headers as $header) {
            $value = (string) ($registration[$header] ?? '');
            $csvRow[] = preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'" . $value : $value;
        }
        $rowStream = fopen('php://temp', 'w+b');
        if ($rowStream === false) {
            throw new RegistrationExportException('Unable to prepare the CSV registration record.');
        }
        if ($csvContent === '') {
            if (fputcsv($rowStream, $headers, ',', '"', '\\', "\r\n") === false) {
                fclose($rowStream);
                throw new RegistrationExportException('Unable to write the CSV registration header.');
            }
        } elseif (!str_ends_with($csvContent, "\n")) {
            $csvContent .= "\r\n";
        }
        if (fputcsv($rowStream, $csvRow, ',', '"', '\\', "\r\n") === false) {
            fclose($rowStream);
            throw new RegistrationExportException('Unable to write the CSV registration record.');
        }
        rewind($rowStream);
        $csvRowContent = stream_get_contents($rowStream);
        fclose($rowStream);
        if ($csvRowContent === false) {
            throw new RegistrationExportException('Unable to read the prepared CSV registration record.');
        }
        $csvContent .= $csvRowContent;

        $records[] = $registration;
        $jsonContent = json_encode(
            $records,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
        ) . PHP_EOL;

        foreach ([$jsonPath => $jsonContent, $csvPath => $csvContent] as $path => $content) {
            $temporaryPath = tempnam($directory, '.student-registrations-');
            if ($temporaryPath === false) {
                throw new RegistrationExportException('Unable to stage a registration export.');
            }
            $temporaryPaths[] = $temporaryPath;
            $bytesWritten = file_put_contents($temporaryPath, $content, LOCK_EX);
            if ($bytesWritten !== strlen($content)) {
                throw new RegistrationExportException('Unable to write a complete registration export.');
            }
        }

        $paths = [$jsonPath, $csvPath];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $backupPath = tempnam($directory, '.student-registrations-backup-');
                if ($backupPath === false || !unlink($backupPath) || !rename($path, $backupPath)) {
                    throw new RegistrationExportException('Unable to preserve the existing registration exports.');
                }
                $backupPaths[$path] = $backupPath;
            }
        }

        foreach ($paths as $index => $path) {
            if (!rename($temporaryPaths[$index], $path)) {
                throw new RegistrationExportException('Unable to publish the registration exports.');
            }
            $installedPaths[] = $path;
        }

        foreach ($backupPaths as $backupPath) {
            if (is_file($backupPath) && !unlink($backupPath)) {
                error_log('Unable to remove a temporary registration export backup.');
            }
        }
        $backupPaths = [];
    } catch (Throwable $exception) {
        foreach ($installedPaths as $path) {
            if (is_file($path) && !unlink($path)) {
                error_log('Unable to remove a partially published registration export.');
            }
        }
        foreach ($backupPaths as $path => $backupPath) {
            if (is_file($backupPath) && !rename($backupPath, $path)) {
                error_log('Unable to restore a previous registration export.');
            }
        }
        if ($exception instanceof RegistrationExportException) {
            throw $exception;
        }
        throw new RegistrationExportException('Unable to update registration exports.', 0, $exception);
    } finally {
        foreach ($temporaryPaths as $path) {
            if (is_file($path) && !unlink($path)) {
                error_log('Unable to remove a temporary registration export file.');
            }
        }
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

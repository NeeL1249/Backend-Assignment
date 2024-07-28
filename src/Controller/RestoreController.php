<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RestoreController extends AbstractController
{
    #[Route('/api/restore', name: 'app_restore', methods: ['POST'])]
    public function index(Request $request): Response
    {
        // We can add authorization here to make sure only admin can restore the database
        $file = $request->files->get('file');

        if(!$file) {
            return $this->json(['error' => 'No file uploaded.'], 400);
        }

        if($file->getClientOriginalExtension() !== 'sql') {
            return $this->json(['error' => 'Invalid file type. Please upload a SQL file.'], 400);
        }

        $backupFile = $file->move(sys_get_temp_dir(), $file->getClientOriginalName());
    
        try {
            $this->restoreDatabase($backupFile->getPathname());
            return $this->json(['message' => 'Database restored successfully.'], 200);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while restoring the database.'], 500);
        }
    }

    private function restoreDatabase(string $backupFile){
        $dbHost = $_ENV['DATABASE_HOST'];
        $dbUser = $_ENV['DATABASE_USER'];
        $dbPass = $_ENV['DATABASE_PASS'];
        $dbName = $_ENV['DATABASE_NAME'];

        $command = sprintf(
            'mysql -h %s -u %s -p%s %s < %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            escapeshellarg($backupFile)
        );

        $output = null;
        $resultCode = null;
        exec($command, $output, $resultCode);

        if ($resultCode !== 0) {
            throw new \RuntimeException('Failed to restore the database.');
        }
    }
}

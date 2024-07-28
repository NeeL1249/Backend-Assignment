<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class UploadController extends AbstractController
{

    private $em;
    private $bus;
    private $mailer;
    
    public function __construct(EntityManagerInterface $em, MessageBusInterface $bus, MailerInterface $mailer)
    {
        $this->em = $em;
        $this->bus = $bus;
        $this->mailer = $mailer;
    }

    #[Route('/api/upload', name: 'app_upload', methods: ['POST'])]
    public function index(Request $request): Response
    {
        $file = $request->files->get('file');

        if(!$file) {
            return $this->json(['error' => 'No file uploaded.'], 400);
        }
        
        if($file->getClientOriginalExtension() !== 'csv') {
            return $this->json(['error' => 'Invalid file type. Please upload a CSV file.'], 400);
        }

        $csv = array_map('str_getcsv', file($file->getPathname()));
        $header = $csv[0];
        unset($csv[0]);

        if (substr($header[0], 0, 3) === "\xef\xbb\xbf") {
            $header[0] = substr($header[0], 3);
        }

        try {
            foreach($csv as $row) {
                $userDetails = array_combine($header, $row);
                $user = new User();
                $user->setName($userDetails['name']);
                $user->setEmail($userDetails['email']);
                $user->setUsername($userDetails['username']);
                $user->setAddress($userDetails['address']);
                $user->setRole($userDetails['role']);
                $this->em->persist($user);
                $email = (new Email())
                    ->from('neel32314@gmail.com')
                    ->to($userDetails['email'])
                    ->subject('Welcome to our platform!')
                    ->text('Welcome to our platform, '.$userDetails['name'].'!');

                try {
                    if($this->mailer->send($email)) {
                        $this->addFlash('success', 'Email sent successfully.');
                    }
                } catch (\Exception $e) {
                    $this->addFlash('error', 'An error occurred while sending the email.'.$e->getMessage());
                }
            }
            $this->em->flush();
            return $this->json(['message' => 'File uploaded successfully.'], 200);
        } catch(\Exception $e) {
            return $this->json(['error' => 'An error occurred while processing the file.'.$e->getMessage()], 500);
        }
    }
}

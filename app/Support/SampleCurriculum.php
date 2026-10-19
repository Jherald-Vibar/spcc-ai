<?php

namespace App\Support;

/**
 * Temporary in-memory data so the student UI works before the lesson tables exist.
 * Replace each method with Eloquent queries (Subject, Lesson, Material...) later;
 * the controller and views only rely on the array shapes returned here.
 */
class SampleCurriculum
{
    public const YEARS = [
        1 => '1st Year',
        2 => '2nd Year',
        3 => '3rd Year',
        4 => '4th Year',
    ];

    /** @return array<string, array{code:string,name:string,year:int}> */
    public static function subjects(): array
    {
        return [
            'IAS2' => ['code' => 'IAS2', 'name' => 'Information Assurance and Security 2', 'year' => 4],
            'CAP2' => ['code' => 'CAP2', 'name' => 'Capstone Project 2', 'year' => 4],
            'IPT1' => ['code' => 'IPT1', 'name' => 'Integrative Programming and Technologies 1', 'year' => 3],
            'NET1' => ['code' => 'NET1', 'name' => 'Networking 1', 'year' => 2],
            'PRG1' => ['code' => 'PRG1', 'name' => 'Computer Programming 1', 'year' => 1],
        ];
    }

    public static function subject(string $code): ?array
    {
        return self::subjects()[strtoupper($code)] ?? null;
    }

    /**
     * Published lessons for a subject. status: completed | in_progress | upcoming
     *
     * @return array<int, array<string, mixed>>
     */
    public static function lessons(string $subjectCode): array
    {
        if (strtoupper($subjectCode) !== 'IAS2') {
            return [];
        }

        $weeks = [
            1 => ['Introduction to Security', 'completed', 'Core security concepts: the CIA triad, threats, vulnerabilities and risk.'],
            2 => ['Classical Encryption Techniques', 'completed', 'Symmetric cipher model, substitution and transposition techniques.'],
            3 => ['Substitution Ciphers', 'completed', 'Caesar, monoalphabetic and Playfair ciphers, and how they are broken.'],
            4 => ['Polyalphabetic Ciphers', 'in_progress', 'Learn about Polyalphabetic Ciphers and the Vigenere cipher.'],
            5 => ['Modern Cryptography', 'upcoming', 'Block ciphers, DES/AES and the basics of public-key cryptography.'],
        ];

        $out = [];
        foreach ($weeks as $week => [$title, $status, $description]) {
            $out[] = [
                'week'        => $week,
                'title'       => $title,
                'status'      => $status,
                'description' => $description,
                'duration'    => '8:45',
                'video_url'   => null, // set to a file/URL once AI video generation is wired up
            ];
        }

        return $out;
    }

    public static function lesson(string $subjectCode, int $week): ?array
    {
        foreach (self::lessons($subjectCode) as $lesson) {
            if ($lesson['week'] === $week) {
                return $lesson;
            }
        }

        return null;
    }

    public static function materials(): array
    {
        return [
            ['name' => 'Week 4 - Polyalphabetic Ciphers.pdf',  'type' => 'PDF',  'size' => '1.2 MB'],
            ['name' => 'Vigenere Cipher Worksheet.docx',        'type' => 'DOCX', 'size' => '240 KB'],
            ['name' => 'Week 4 Lecture Slides.pptx',            'type' => 'PPTX', 'size' => '3.4 MB'],
        ];
    }

    public static function reviewer(): array
    {
        return [
            [
                'q'       => 'Which cipher uses a repeating keyword to select a different Caesar shift for each letter?',
                'options' => ['Playfair cipher', 'Vigenere cipher', 'Rail fence cipher', 'Atbash cipher'],
                'answer'  => 1,
            ],
            [
                'q'       => 'Why are polyalphabetic ciphers harder to break by simple frequency analysis than monoalphabetic ciphers?',
                'options' => [
                    'They use longer plaintexts',
                    'One plaintext letter can map to several ciphertext letters',
                    'They never repeat the key',
                    'They only encrypt vowels',
                ],
                'answer'  => 1,
            ],
            [
                'q'       => 'Which technique is commonly used to estimate the key length of a Vigenere cipher?',
                'options' => ['Kasiski examination', 'Brute-force hashing', 'Diffie-Hellman', 'Padding oracle'],
                'answer'  => 0,
            ],
        ];
    }

    public static function forumPosts(): array
    {
        return [
            ['author' => 'Maria Santos', 'time' => '2 hours ago', 'body' => 'Can someone explain how the key repeats when the message is longer than the keyword?', 'replies' => 3],
            ['author' => 'Prof. Santos', 'time' => 'Yesterday',   'body' => 'Reminder: the Week 4 worksheet is due before our next meeting. Post questions here.',        'replies' => 1],
        ];
    }
}

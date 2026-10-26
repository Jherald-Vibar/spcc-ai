<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\LessonFile;
use App\Models\LessonProgress;
use App\Models\LessonReport;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/** Sample subjects and lessons so the student pages have real rows to show. Safe to run repeatedly. */
class CurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('email', 'santos_maria@spcc.edu.ph')->first();
        $student = User::where('email', 'delacruz_juan@spcc.edu.ph')->first();

        $subjects = [
            ['IAS2', 'Information Assurance and Security 2', 4],
            ['CAP2', 'Capstone Project 2', 4],
            ['IPT1', 'Integrative Programming and Technologies 1', 3],
            ['NET1', 'Networking 1', 2],
            ['PRG1', 'Computer Programming 1', 1],
        ];

        foreach ($subjects as [$code, $name, $year]) {
            Subject::updateOrCreate(['code' => $code], [
                'name' => $name, 'year' => $year, 'teacher_id' => $teacher?->id,
            ]);
        }

        $ias2 = Subject::where('code', 'IAS2')->first();

        $weeks = [
            1 => ['Introduction to Security', 'Core security concepts: the CIA triad, threats, vulnerabilities and risk.'],
            2 => ['Classical Encryption Techniques', 'Symmetric cipher model, substitution and transposition techniques.'],
            3 => ['Substitution Ciphers', 'Caesar, monoalphabetic and Playfair ciphers, and how they are broken.'],
            4 => ['Polyalphabetic Ciphers', 'Learn about Polyalphabetic Ciphers and the Vigenere cipher.'],
            5 => ['Modern Cryptography', 'Block ciphers, DES/AES and the basics of public-key cryptography.'],
        ];

        foreach ($weeks as $week => [$title, $description]) {
            Lesson::updateOrCreate(['subject_id' => $ias2->id, 'week' => $week], [
                'title' => $title,
                'created_by' => $teacher?->id,
                'description' => $description,
                'duration' => '8:45',
                // week 5 stays a draft so you can see that students do not get it
                'published_at' => $week <= 4 ? now()->subDays(10 - $week) : null,
            ]);
        }

        // one small real file on the private disk, attached to week 4
        $week4 = Lesson::where('subject_id', $ias2->id)->where('week', 4)->first();
        $path = 'lesson-files/sample-week4-notes.txt';
        Storage::disk('local')->put($path, "Week 4 - Polyalphabetic Ciphers\nSample material created by CurriculumSeeder.\n");
        LessonFile::updateOrCreate(['lesson_id' => $week4->id, 'path' => $path], [
            'name' => 'Week 4 - Polyalphabetic Ciphers.txt',
            'extension' => 'txt',
            'bytes' => Storage::disk('local')->size($path),
        ]);

        // a reported lesson in another subject so the admin "Reported" tab has something to show
        $net1 = Subject::where('code', 'NET1')->first();
        $topologies = Lesson::updateOrCreate(['subject_id' => $net1->id, 'week' => 2], [
            'title' => 'Network Topologies',
            'created_by' => $teacher?->id,
            'description' => 'Bus, star, ring and mesh topologies.',
            'published_at' => now()->subDays(3),
        ]);
        if ($student && ! $topologies->reports()->exists()) {
            LessonReport::create([
                'lesson_id' => $topologies->id,
                'user_id' => $student->id,
                'reason' => 'The ring topology diagram looks wrong.',
            ]);
        }

        if ($student) {
            foreach ([1 => 'completed', 2 => 'completed', 3 => 'completed'] as $week => $status) {
                $lesson = Lesson::where('subject_id', $ias2->id)->where('week', $week)->first();
                LessonProgress::updateOrCreate(
                    ['user_id' => $student->id, 'lesson_id' => $lesson->id],
                    ['status' => $status, 'completed_at' => now()->subDays(5)],
                );
            }
        }
    }
}

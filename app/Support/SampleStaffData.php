<?php

namespace App\Support;

/**
 * Temporary in-memory data for the teacher and admin screens.
 * Replace each method with real queries; the views rely only on the array shapes.
 */
class SampleStaffData
{
    /* ---------------------------------------------------------------- teacher */

    /** @return array<int, array{week:int,title:string,status:string}> status: published | draft */
    public static function teacherLessons(string $subjectCode): array
    {
        if (strtoupper($subjectCode) !== 'IAS2') {
            return [];
        }

        return [
            ['week' => 1, 'title' => 'Introduction to Security',      'status' => 'published'],
            ['week' => 2, 'title' => 'Classical Encryption Techniques', 'status' => 'published'],
            ['week' => 3, 'title' => 'Substitution Ciphers',          'status' => 'published'],
            ['week' => 4, 'title' => 'Polyalphabetic Ciphers',        'status' => 'draft'],
            ['week' => 5, 'title' => 'Modern Cryptography',           'status' => 'draft'],
        ];
    }

    public static function teacherAnalytics(): array
    {
        return [
            ['label' => 'Enrolled students', 'value' => '42'],
            ['label' => 'Avg. lesson completion', 'value' => '78%'],
            ['label' => 'AI chat questions (7 days)', 'value' => '135'],
            ['label' => 'Avg. reviewer score', 'value' => '81%'],
        ];
    }

    /* ------------------------------------------------------------------ admin */

    public static function stats(): array
    {
        return [
            'users'    => 1245,
            'students' => 1102,
            'teachers' => 120,
            'lessons'  => 85,
            'growth'   => 12,
        ];
    }

    /** 30 daily "active users" values, deterministic so the chart is stable. */
    public static function usage(): array
    {
        $out = [];
        for ($i = 0; $i < 30; $i++) {
            $out[] = (int) round(700 + 220 * sin($i / 4) + $i * 9 + (($i * 37) % 60));
        }

        return $out;
    }

    public static function activities(): array
    {
        return [
            ['title' => 'New lesson published',  'sub' => 'IAS2 – Week 4',         'time' => 'Oct 19, 2:15 PM',  'tone' => 'bg-green-100 text-green-600',   'icon' => 'book-open'],
            ['title' => 'New user registered',   'sub' => 'Student account',        'time' => 'Oct 19, 1:42 PM',  'tone' => 'bg-blue-100 text-blue-600',     'icon' => 'user'],
            ['title' => 'Lesson updated',        'sub' => 'Week 3 – Files added',   'time' => 'Oct 18, 11:30 AM', 'tone' => 'bg-orange-100 text-orange-600', 'icon' => 'document'],
            ['title' => 'Report submitted',      'sub' => 'Out-of-scope question',  'time' => 'Oct 18, 9:20 AM',  'tone' => 'bg-violet-100 text-violet-600', 'icon' => 'chat'],
        ];
    }

    /** @return array<int, array{name:string,email:string,role:string,status:string}> role: student|teacher|administrator */
    public static function users(): array
    {
        return [
            ['name' => 'Dela Cruz, Juan',  'email' => 'delacruz_juan@spcc.edu.ph',  'role' => 'student',       'status' => 'active'],
            ['name' => 'Santos, Maria',    'email' => 'santos_maria@spcc.edu.ph',   'role' => 'student',       'status' => 'active'],
            ['name' => 'Reyes, Mark',      'email' => 'reyes_mark@spcc.edu.ph',     'role' => 'student',       'status' => 'active'],
            ['name' => 'Bautista, Ana',    'email' => 'bautista_ana@spcc.edu.ph',   'role' => 'student',       'status' => 'inactive'],
            ['name' => 'Garcia, Liza',     'email' => 'garcia_liza@spcc.edu.ph',    'role' => 'teacher',       'status' => 'inactive'],
            ['name' => 'Santos, Prof. Ric', 'email' => 'santos_ric@spcc.edu.ph',    'role' => 'teacher',       'status' => 'active'],
            ['name' => 'Mendoza, Paula',   'email' => 'mendoza_paula@spcc.edu.ph',  'role' => 'teacher',       'status' => 'active'],
            ['name' => 'Admin, System',    'email' => 'admin_system@spcc.edu.ph',   'role' => 'administrator', 'status' => 'active'],
        ];
    }

    /** @return array<int, array{week:int,title:string,subject:string,by:string,status:string}> */
    public static function lessons(): array
    {
        return [
            ['week' => 1, 'title' => 'Introduction to Security',        'subject' => 'IAS2', 'by' => 'Prof. Santos', 'status' => 'published'],
            ['week' => 2, 'title' => 'Classical Encryption Techniques', 'subject' => 'IAS2', 'by' => 'Prof. Santos', 'status' => 'published'],
            ['week' => 3, 'title' => 'Substitution Ciphers',            'subject' => 'IAS2', 'by' => 'Prof. Santos', 'status' => 'published'],
            ['week' => 4, 'title' => 'Polyalphabetic Ciphers',          'subject' => 'IAS2', 'by' => 'Prof. Santos', 'status' => 'draft'],
            ['week' => 5, 'title' => 'Modern Cryptography',             'subject' => 'IAS2', 'by' => 'Prof. Santos', 'status' => 'pending'],
            ['week' => 2, 'title' => 'Network Topologies',              'subject' => 'NET1', 'by' => 'Garcia, Liza', 'status' => 'reported'],
        ];
    }
}

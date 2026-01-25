<?php
session_start();
require_once '../../backend/vite_helper.php';
require_once '../../config/db.php';

// Security check: Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$firstname = htmlspecialchars($_SESSION['firstname']);
$lastname = htmlspecialchars($_SESSION['lastname']);
$position = htmlspecialchars($_SESSION['position']);
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../frontend/images/spc.png">
    <title>About Us | OSAS SIS</title>
    <?= vite(['backend/js/main.js', 'frontend/css/styles.css']) ?>
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
    </style>
</head>
<body class="h-full font-['Plus_Jakarta_Sans',sans-serif]">
    
    <!-- Include Sidebar -->
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <div class="ml-64 min-h-screen bg-slate-50/50">
        
        <!-- Header -->
        <div class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-slate-200/60">
            <div class="px-8 py-5 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">About Our Team</h1>
                    <p class="mt-1 text-sm font-medium text-slate-500">Meet the developers behind OSAS SIS</p>
                </div>
            </div>
        </div>

        <div class="px-8 py-8 space-y-8">
            <!-- Staff List Section -->
            <section class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 lg:p-12">
                <div class="mx-auto mb-8 max-w-screen-sm lg:mb-12 text-center">
                    <h2 class="mb-4 text-4xl tracking-tight font-extrabold text-gray-900">Our Team</h2>
                    <p class="font-light text-gray-500 sm:text-xl">The talented developers who built the OSAS Sports Equipment Management & Storage Management System</p>
                </div> 
                
                <!-- Top 3 Developers Row -->
                <div class="grid gap-8 lg:gap-12 sm:grid-cols-2 md:grid-cols-3 mb-8">
                    <!-- Jesper Ian Barilla - First (Highlighted) -->
                    <div class="text-center text-gray-500 transition-all duration-300 ease-[cubic-bezier(0.25,0.8,0.25,1)] hover:-translate-y-1 hover:shadow-[0_15px_30px_-5px_rgba(0,0,0,0.1)] cursor-pointer relative">
                        <div class="absolute -top-2 -right-2 bg-emerald-500 text-white text-xs font-bold px-2 py-1 rounded-full">Lead</div>
                        <div class="mx-auto mb-4 w-36 h-36 rounded-full overflow-hidden shadow-lg bg-gradient-to-br from-[#800020] to-[#5c0016] flex items-center justify-center">
                            <img src="../images/about/jesperianbarila.png" alt="Jesper Ian Barilla" class="w-full h-full object-cover">
                        </div>
                        <h3 class="mb-1 text-2xl font-bold tracking-tight text-gray-900">
                            Jesper Ian Barilla
                        </h3>
                        <p class="mb-2 text-gray-600 font-medium">Lead Full-Stack Developer</p>
                        <p class="text-sm text-gray-500 mb-4">Built the entire Sports Equipment CRUD system, enhanced login frontend design, improved Storage Management with cabinet UI/UX enhancements, advanced search functionality, export features, and overall system improvements</p>
                        <ul class="flex justify-center mt-4 space-x-4">
                            <li>
                                <a href="https://www.facebook.com/jesper.ian.villacorte.barila/" class="text-[#39569c] hover:text-gray-900 transition-colors cursor-pointer" target="_blank" rel="noopener noreferrer">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" /></svg>
                                </a>
                            </li>
                            <li>
                                <a href="https://www.tiktok.com/@yansanity_23" class="text-gray-900 hover:text-gray-900 transition-colors cursor-pointer" target="_blank" rel="noopener noreferrer">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg>
                                </a>
                            </li>
                            <li>
                                <a href="https://github.com/yansanity1998" class="text-gray-900 hover:text-gray-900 transition-colors cursor-pointer" target="_blank" rel="noopener noreferrer">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" /></svg>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Casan Macaan -->
                    <div class="text-center text-gray-500 transition-all duration-300 ease-[cubic-bezier(0.25,0.8,0.25,1)] hover:-translate-y-1 hover:shadow-[0_15px_30px_-5px_rgba(0,0,0,0.1)] cursor-pointer">
                    <div class="mx-auto mb-4 w-36 h-36 rounded-full overflow-hidden shadow-lg bg-gradient-to-br from-[#800020] to-[#5c0016] flex items-center justify-center">
                            <img src="../images/about/casanmacaan.jpg" alt="Casan Macaan" class="w-full h-full object-cover">
                        </div>
                        <h3 class="mb-1 text-2xl font-bold tracking-tight text-gray-900">
                            Casan Macaan
                        </h3>
                        <p class="mb-2 text-gray-600 font-medium">Database Architect</p>
                        <p class="text-sm text-gray-500 mb-4">Designed the entire database structure, ERD, table schemas, and system flow architecture</p>
                        <ul class="flex justify-center mt-4 space-x-4">
                            <li>
                                <a href="https://www.facebook.com/casan.macaan" class="text-[#39569c] hover:text-gray-900 transition-colors cursor-pointer" target="_blank" rel="noopener noreferrer">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" /></svg>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="text-gray-900 hover:text-gray-900 transition-colors cursor-pointer" target="_blank" rel="noopener noreferrer">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg>
                                </a>
                            </li>
                            <li>
                                <a href="https://github.com/Macaan2024" class="text-gray-900 hover:text-gray-900 transition-colors cursor-pointer" target="_blank" rel="noopener noreferrer">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" /></svg>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Mark Jordan Ugtong -->
                    <div class="text-center text-gray-500 transition-all duration-300 ease-[cubic-bezier(0.25,0.8,0.25,1)] hover:-translate-y-1 hover:shadow-[0_15px_30px_-5px_rgba(0,0,0,0.1)] cursor-pointer">
                        <div class="mx-auto mb-4 w-36 h-36 rounded-full overflow-hidden shadow-lg bg-gradient-to-br from-[#800020] to-[#5c0016] flex items-center justify-center">
                            <img src="../images/about/markjordanugtong.jpg" alt="Mark Jordan Ugtong" class="w-full h-full object-cover">
                        </div>
                        <h3 class="mb-1 text-2xl font-bold tracking-tight text-gray-900">
                            Mark Jordan Ugtong
                        </h3>
                        <p class="mb-2 text-gray-600 font-medium">Storage Management Foundation Developer</p>
                        <p class="text-sm text-gray-500 mb-4">Created the login form backend, folder structure, database configuration, and Storage Management foundation including basic adding functionality, simple search, category system, and table structures</p>
                        <ul class="flex justify-center mt-4 space-x-4">
                            <li>
                                <a href="https://www.facebook.com/analyn.polasko" class="text-[#39569c] hover:text-gray-900 transition-colors cursor-pointer" target="_blank" rel="noopener noreferrer">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" /></svg>
                                </a>
                            </li>
                            <li>
                                <a href="https://www.tiktok.com/@wizkhalifax_" class="text-gray-900 hover:text-gray-900 transition-colors cursor-pointer" target="_blank" rel="noopener noreferrer">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg>
                                </a>
                            </li>
                            <li>
                                <a href="https://github.com/markjordanugtongspc" class="text-gray-900 hover:text-gray-900 transition-colors cursor-pointer" target="_blank" rel="noopener noreferrer">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" /></svg>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Bottom Row - Single Member (Centered) -->
                <div class="flex justify-center">
                    <div class="text-center text-gray-500 transition-all duration-300 ease-[cubic-bezier(0.25,0.8,0.25,1)] hover:-translate-y-1 hover:shadow-[0_15px_30px_-5px_rgba(0,0,0,0.1)] cursor-pointer max-w-sm">
                        <!-- Stefen Harvey Alonzo -->
                        <div class="mx-auto mb-4 w-36 h-36 rounded-full overflow-hidden shadow-lg bg-gradient-to-br from-[#800020] to-[#5c0016] flex items-center justify-center">
                            <img src="../images/about/stefenharveyalonzo.jpg" alt="Stefen Harvey Alonzo" class="w-full h-full object-cover">
                        </div>
                        <h3 class="mb-1 text-2xl font-bold tracking-tight text-gray-900">
                            Stefen Harvey Alonzo
                        </h3>
                        <p class="mb-2 text-gray-600 font-medium">QA Tester & System Analyst</p>
                        <p class="text-sm text-gray-500 mb-4">Tested the entire system, reported bugs, added items to the system, and contributed to CRUD operations</p>
                        <ul class="flex justify-center mt-4 space-x-4">
                            <li>
                                <a href="https://www.facebook.com/walccc" class="text-[#39569c] hover:text-gray-900 transition-colors cursor-pointer" target="_blank" rel="noopener noreferrer">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" /></svg>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="text-gray-900 hover:text-gray-900 transition-colors cursor-pointer" target="_blank" rel="noopener noreferrer">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="text-gray-900 hover:text-gray-900 transition-colors cursor-pointer" target="_blank" rel="noopener noreferrer">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" /></svg>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Contact Information Accordion -->
            <section class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 lg:p-12">
                <div class="mx-auto mb-8 max-w-screen-sm lg:mb-12 text-center">
                    <h2 class="mb-4 text-4xl tracking-tight font-extrabold text-gray-900">Contact Information</h2>
                    <p class="font-light text-gray-500 sm:text-xl">Get in touch with our team members</p>
                </div>

                <div id="accordion-collapse" data-accordion="collapse" class="rounded-lg border border-slate-200 overflow-hidden shadow-sm max-w-4xl mx-auto">
                    <!-- Jesper Ian Barilla -->
                    <h2 id="accordion-collapse-heading-1">
                        <button type="button" class="flex items-center justify-between w-full p-5 font-medium text-gray-900 rounded-t-lg border-b border-slate-200 hover:bg-slate-50 gap-3 cursor-pointer" data-accordion-target="#accordion-collapse-body-1" aria-expanded="true" aria-controls="accordion-collapse-body-1">
                            <span>Jesper Ian Barilla - Lead Full-Stack Developer</span>
                            <svg data-accordion-icon class="w-5 h-5 rotate-180 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 15 7-7 7 7"></path>
                            </svg>
                        </button>
                    </h2>
                    <div id="accordion-collapse-body-1" class="hidden border-b border-slate-200" aria-labelledby="accordion-collapse-heading-1">
                        <div class="p-5">
                            <p class="mb-4 text-gray-600">Contact Jesper for questions about the Sports Equipment CRUD system, login frontend design, Storage Management enhancements (cabinet UI/UX, advanced search, export features), animations, and overall system improvements.</p>
                            <ul class="space-y-3">
                                <li class="flex items-center text-gray-700">
                                    <svg class="w-5 h-5 mr-3 text-[#800020]" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                    </svg>
                                    <a href="mailto:jesperianbarila.202101066@gmail.com" class="hover:text-[#800020] transition-colors cursor-pointer">jesperianbarila.202101066@gmail.com</a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Casan Macaan -->
                    <h2 id="accordion-collapse-heading-2">
                        <button type="button" class="flex items-center justify-between w-full p-5 font-medium text-gray-900 border-b border-slate-200 hover:bg-slate-50 gap-3 cursor-pointer" data-accordion-target="#accordion-collapse-body-2" aria-expanded="false" aria-controls="accordion-collapse-body-2">
                            <span>Casan Macaan - Database Architect</span>
                            <svg data-accordion-icon class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 15 7-7 7 7"></path>
                            </svg>
                        </button>
                    </h2>
                    <div id="accordion-collapse-body-2" class="hidden border-b border-slate-200" aria-labelledby="accordion-collapse-heading-2">
                        <div class="p-5">
                            <p class="mb-4 text-gray-600">Contact Casan for questions about database structure, ERD, table schemas, and system architecture.</p>
                            <ul class="space-y-3">
                                <li class="flex items-center text-gray-700">
                                    <svg class="w-5 h-5 mr-3 text-[#800020]" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                    </svg>
                                    <a href="mailto:macaancasan1@gmail.com" class="hover:text-[#800020] transition-colors cursor-pointer">macaancasan1@gmail.com</a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Mark Jordan Ugtong -->
                    <h2 id="accordion-collapse-heading-3">
                        <button type="button" class="flex items-center justify-between w-full p-5 font-medium text-gray-900 border-b border-slate-200 hover:bg-slate-50 gap-3 cursor-pointer" data-accordion-target="#accordion-collapse-body-3" aria-expanded="false" aria-controls="accordion-collapse-body-3">
                            <span>Mark Jordan Ugtong - Storage Management Foundation Developer</span>
                            <svg data-accordion-icon class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 15 7-7 7 7"></path>
                            </svg>
                        </button>
                    </h2>
                    <div id="accordion-collapse-body-3" class="hidden border-b border-slate-200" aria-labelledby="accordion-collapse-heading-3">
                        <div class="p-5">
                            <p class="mb-4 text-gray-600">Contact Mark for questions about the login form backend, Storage Management foundation, folder structure, and database configuration.</p>
                            <ul class="space-y-3">
                                <li class="flex items-center text-gray-700">
                                    <svg class="w-5 h-5 mr-3 text-[#800020]" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                    </svg>
                                    <a href="mailto:markjordanugtong.202200752@gmail.com" class="hover:text-[#800020] transition-colors cursor-pointer">markjordanugtong.202200752@gmail.com</a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Stefen Harvey Alonzo -->
                    <h2 id="accordion-collapse-heading-4">
                        <button type="button" class="flex items-center justify-between w-full p-5 font-medium text-gray-900 rounded-b-lg hover:bg-slate-50 gap-3 cursor-pointer" data-accordion-target="#accordion-collapse-body-4" aria-expanded="false" aria-controls="accordion-collapse-body-4">
                            <span>Stefen Harvey Alonzo - QA Tester & System Analyst</span>
                            <svg data-accordion-icon class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 15 7-7 7 7"></path>
                            </svg>
                        </button>
                    </h2>
                    <div id="accordion-collapse-body-4" class="hidden" aria-labelledby="accordion-collapse-heading-4">
                        <div class="p-5 border-t border-slate-200">
                            <p class="mb-4 text-gray-600">Contact Stefen for questions about system testing, bug reports, and system analysis.</p>
                            <ul class="space-y-3">
                                <li class="flex items-center text-gray-700">
                                    <svg class="w-5 h-5 mr-3 text-[#800020]" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                    </svg>
                                    <a href="mailto:stefen.alonzo@example.com" class="hover:text-[#800020] transition-colors cursor-pointer">stefen.alonzo@example.com</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Contact Form Section -->
            <section class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 lg:p-12">
                <div class="max-w-5xl mx-auto">
                    <div class="text-center px-6 mb-12">
                        <h2 class="text-slate-900 text-3xl font-bold">Contact Us</h2>
                        <p class="text-[15px] text-slate-600 mt-4">Have questions or need assistance? Reach out to our team!</p>
                    </div>

                    <div class="grid lg:grid-cols-5 items-center p-2 shadow-lg rounded-lg">
                        <div class="lg:col-span-2 bg-[#800020] rounded-lg p-6 h-full max-lg:order-1 relative overflow-hidden max-lg:mt-12">
                            <h3 class="text-[24px] text-white font-medium">Contact Information</h3>
                            <p class="text-[15px] text-slate-300 leading-relaxed mt-4">Select a team member from the dropdown to send them a message directly.</p>
                            <div class="mt-16 relative z-50">
                                <ul class="space-y-8">
                                    <li class="flex items-center text-slate-200 hover:text-white transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" width="16px" height="16px" viewBox="0 0 479.058 479.058" class="mr-4">
                                            <path d="M434.146 59.882H44.912C20.146 59.882 0 80.028 0 104.794v269.47c0 24.766 20.146 44.912 44.912 44.912h389.234c24.766 0 44.912-20.146 44.912-44.912v-269.47c0-24.766-20.146-44.912-44.912-44.912zm0 29.941c2.034 0 3.969.422 5.738 1.159L239.529 264.631 39.173 90.982a14.902 14.902 0 0 1 5.738-1.159zm0 299.411H44.912c-8.26 0-14.971-6.71-14.971-14.971V122.615l199.778 173.141c2.822 2.441 6.316 3.655 9.81 3.655s6.988-1.213 9.81-3.655l199.778-173.141v251.649c-.001 8.26-6.711 14.97-14.971 14.97z"/>
                                        </svg>
                                        <span id="contactInfoEmail" class="text-[15px]">team@osas-sis.com</span>
                                    </li>
                                    <li class="flex items-center text-slate-200 hover:text-white transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16px" height="16px" class="mr-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443a55.381 55.381 0 015.25 2.882V15m-9 0v-1.5a48.836 48.836 0 00-1.5-.75h-1.5m9 0v-1.5a48.836 48.836 0 011.5-.75h1.5m-9 0H6.75m9 0H18m-9 0v-1.5m0 0H6.75m0 0H4.5m0 0v1.5m0 0h1.5m0 0H6.75" />
                                        </svg>
                                        <span id="contactInfoSchool" class="text-[15px]">St. Peter's College, Iligan City</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="absolute -bottom-24 -right-24 w-60 h-60 rounded-full bg-white/10"></div>
                        </div>

                        <div class="px-4 sm:px-8 py-4 lg:col-span-3">
                            <form id="contactForm" method="POST" action="#" class="contact-form">
                                <!-- FormSubmit will redirect back to this page after submission -->
                                <input type="hidden" name="_next" value="<?= htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '?submitted=true') ?>">
                                <input type="hidden" name="_captcha" value="false">
                                <input type="hidden" name="_template" value="table">
                                
                                <div class="mb-6">
                                    <label for="contactMember" class="block text-sm font-medium text-slate-900 mb-2">Select Team Member <span class="text-red-500">*</span></label>
                                    <select id="contactMember" name="team_member" required class="w-full px-3 py-2.5 bg-white text-sm text-slate-900 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800020] focus:border-[#800020] outline-none cursor-pointer">
                                        <option value="">Choose a team member...</option>
                                        <option value="jesper">Jesper Ian Barilla - Lead Full-Stack Developer</option>
                                        <option value="casan">Casan Macaan - Database Architect</option>
                                        <option value="mark">Mark Jordan Ugtong - Storage Management Foundation Developer</option>
                                        <option value="stefen">Stefen Harvey Alonzo - QA Tester & System Analyst</option>
                                    </select>
                                    <p id="selectedEmail" class="mt-2 text-xs text-slate-500 hidden"></p>
                                </div>

                                <div class="grid md:grid-cols-2 gap-6">
                                    <div class="relative flex items-center">
                                        <input type="text" name="first_name" placeholder="First Name" required class="px-2 py-3 bg-white w-full text-sm text-slate-900 border-b border-gray-300 focus:border-[#800020] outline-none" />
                                    </div>
                                    <div class="relative flex items-center">
                                        <input type="text" name="last_name" placeholder="Last Name" required class="px-2 py-3 bg-white w-full text-sm text-slate-900 border-b border-gray-300 focus:border-[#800020] outline-none" />
                                    </div>
                                    <div class="relative flex items-center">
                                        <input type="email" name="email" placeholder="Your Email" required class="px-2 py-3 bg-white w-full text-sm text-slate-900 border-b border-gray-300 focus:border-[#800020] outline-none" />
                                    </div>
                                    <div class="relative flex items-center">
                                        <input type="tel" name="phone" placeholder="Phone No." class="px-2 py-3 bg-white w-full text-sm text-slate-900 border-b border-gray-300 focus:border-[#800020] outline-none" />
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <textarea name="message" placeholder="Write Message" rows="4" required class="px-2 pt-3 bg-white w-full text-sm text-slate-900 border-b border-gray-300 focus:border-[#800020] outline-none"></textarea>
                                </div>

                                <button type="submit" id="submitBtn" class="mt-8 flex items-center justify-center text-sm font-medium w-full rounded-lg px-4 py-3 tracking-wide cursor-pointer text-white bg-[#800020] hover:bg-[#5c0016] transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" fill='#fff' class="mr-2" viewBox="0 0 548.244 548.244">
                                        <path fill-rule="evenodd" d="M392.19 156.054 211.268 281.667 22.032 218.58C8.823 214.168-.076 201.775 0 187.852c.077-13.923 9.078-26.24 22.338-30.498L506.15 1.549c11.5-3.697 24.123-.663 32.666 7.88 8.542 8.543 11.577 21.165 7.879 32.666L390.89 525.906c-4.258 13.26-16.575 22.261-30.498 22.338-13.923.076-26.316-8.823-30.728-22.032l-63.393-190.153z" clip-rule="evenodd" data-original="#000000" />
                                    </svg>
                                    <span id="submitBtnText">Send Message</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Documentation/Wiki Section -->
            <section class="bg-gradient-to-br from-[#800020] to-[#5c0016] rounded-2xl shadow-lg border border-slate-100 p-8 lg:p-12 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -mr-32 -mt-32"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full -ml-24 -mb-24"></div>
                
                <div class="max-w-4xl mx-auto relative z-10">
                    <div class="flex flex-col md:flex-row items-center gap-6 md:gap-8">
                        <div class="flex-shrink-0">
                            <div class="w-20 h-20 bg-white/10 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-white">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-3.75c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                </svg>
                            </div>
                        </div>
                        
                        <div class="flex-1 text-center md:text-left">
                            <h2 class="text-2xl md:text-3xl font-bold text-white mb-2">System Documentation & Wiki</h2>
                            <p class="text-slate-200 text-sm md:text-base mb-4 leading-relaxed">
                                Learn how to use the OSAS SIS system effectively. Access our comprehensive documentation, user manual, and wiki guides to get the most out of the system.
                            </p>
                            <a href="https://github.com/markjordanugtongspc/OSAS-SIS/wiki" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-6 py-3 bg-white text-[#800020] font-semibold rounded-lg hover:bg-slate-100 transition-all duration-200 shadow-lg hover:shadow-xl cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 16.875h3.375m0 0h3.375m-3.375 0V13.5m0 3.375v3.375M6 10.5h2.25a2.25 2.25 0 002.25-2.25V6a2.25 2.25 0 00-2.25-2.25H6A2.25 2.25 0 003.75 6v2.25A2.25 2.25 0 006 10.5zm0 9.75h2.25A2.25 2.25 0 0010.5 18v-2.25a2.25 2.25 0 00-2.25-2.25H6a2.25 2.25 0 00-2.25 2.25V18A2.25 2.25 0 006 20.25zm9.75-9.75H18a2.25 2.25 0 002.25-2.25V6A2.25 2.25 0 0018 3.75h-2.25A2.25 2.25 0 0013.5 6v2.25a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                View Documentation
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Initialize Flowbite Accordion -->
    <script>
        (function() {
            let accordionInitialized = false;
            
            function initAccordion() {
                if (accordionInitialized) return;
                accordionInitialized = true;
                
                const accordionButtons = document.querySelectorAll('[data-accordion-target]');
                
                accordionButtons.forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        const targetId = this.getAttribute('data-accordion-target');
                        const targetElement = document.querySelector(targetId);
                        const icon = this.querySelector('[data-accordion-icon]');
                        
                        if (targetElement) {
                            const isExpanded = this.getAttribute('aria-expanded') === 'true';
                            const allButtons = document.querySelectorAll('[data-accordion-target]');
                            
                            // Close all other accordions
                            allButtons.forEach(btn => {
                                if (btn !== button) {
                                    const otherTargetId = btn.getAttribute('data-accordion-target');
                                    const otherTarget = document.querySelector(otherTargetId);
                                    const otherIcon = btn.querySelector('[data-accordion-icon]');
                                    
                                    if (otherTarget) {
                                        otherTarget.classList.add('hidden');
                                        btn.setAttribute('aria-expanded', 'false');
                                        if (otherIcon) {
                                            otherIcon.classList.remove('rotate-180');
                                        }
                                    }
                                }
                            });
                            
                            // Toggle current accordion
                            if (isExpanded) {
                                targetElement.classList.add('hidden');
                                this.setAttribute('aria-expanded', 'false');
                                if (icon) {
                                    icon.classList.remove('rotate-180');
                                }
                            } else {
                                targetElement.classList.remove('hidden');
                                this.setAttribute('aria-expanded', 'true');
                                if (icon) {
                                    icon.classList.add('rotate-180');
                                }
                            }
                        }
                    });
                });
            }

            // Initialize on page load
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initAccordion);
            } else {
                initAccordion();
            }

            // Make it available globally for SPA navigation re-initialization
            window.initAccordion = function() {
                accordionInitialized = false;
                initAccordion();
            };
        })();
    </script>

    <!-- Contact Form - FormSubmit Integration -->
    <script>
        (function() {
            // Team member email mapping
            const teamEmails = {
                'jesper': 'jesperianbarila.202101066@gmail.com',
                'casan': 'macaancasan1@gmail.com',
                'mark': 'markjordanugtong.202200752@gmail.com',
                'stefen': 'stefen.alonzo@example.com'
            };

            // Team member names for subject line
            const teamNames = {
                'jesper': 'Jesper Ian Barilla',
                'casan': 'Casan Macaan',
                'mark': 'Mark Jordan Ugtong',
                'stefen': 'Stefen Harvey Alonzo'
            };

            // Team member school information
            const teamSchools = {
                'jesper': 'St. Peter\'s College, Iligan City',
                'casan': 'St. Peter\'s College, Iligan City',
                'mark': 'St. Peter\'s College, Iligan City',
                'stefen': 'St. Peter\'s College, Iligan City'
            };

            const contactForm = document.getElementById('contactForm');
            const contactMemberSelect = document.getElementById('contactMember');
            const selectedEmailDisplay = document.getElementById('selectedEmail');
            const contactInfoEmail = document.getElementById('contactInfoEmail');
            const contactInfoSchool = document.getElementById('contactInfoSchool');
            const submitBtn = document.getElementById('submitBtn');
            const submitBtnText = document.getElementById('submitBtnText');

            // Update form action when team member is selected
            function updateFormAction() {
                const selectedValue = contactMemberSelect.value;
                
                if (selectedValue && teamEmails[selectedValue]) {
                    const email = teamEmails[selectedValue];
                    const teamName = teamNames[selectedValue];
                    const school = teamSchools[selectedValue] || 'St. Peter\'s College, Iligan City';
                    
                    // Update form action to FormSubmit API
                    contactForm.action = `https://formsubmit.co/${email}`;
                    
                    // Show selected email in form
                    selectedEmailDisplay.textContent = `Message will be sent to: ${email}`;
                    selectedEmailDisplay.classList.remove('hidden');
                    
                    // Update contact information section
                    if (contactInfoEmail) {
                        contactInfoEmail.textContent = email;
                    }
                    if (contactInfoSchool) {
                        contactInfoSchool.textContent = school;
                    }
                    
                    // Enable submit button
                    submitBtn.disabled = false;
                    
                    // Add subject line
                    let subjectInput = document.querySelector('input[name="_subject"]');
                    if (!subjectInput) {
                        subjectInput = document.createElement('input');
                        subjectInput.type = 'hidden';
                        subjectInput.name = '_subject';
                        contactForm.appendChild(subjectInput);
                    }
                    subjectInput.value = `Contact from OSAS SIS - ${teamName}`;
                } else {
                    // Reset form action
                    contactForm.action = '#';
                    selectedEmailDisplay.classList.add('hidden');
                    submitBtn.disabled = true;
                    
                    // Reset contact information to default
                    if (contactInfoEmail) {
                        contactInfoEmail.textContent = 'team@osas-sis.com';
                    }
                    if (contactInfoSchool) {
                        contactInfoSchool.textContent = 'St. Peter\'s College, Iligan City';
                    }
                }
            }

            // Handle form submission
            function handleFormSubmit(e) {
                const selectedValue = contactMemberSelect.value;
                
                if (!selectedValue || !teamEmails[selectedValue]) {
                    e.preventDefault();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Team Member Required',
                            text: 'Please select a team member to contact.',
                            confirmButtonColor: '#800020',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        alert('Please select a team member to contact.');
                    }
                    contactMemberSelect.focus();
                    return false;
                }

                // Show loading state
                submitBtn.disabled = true;
                submitBtnText.textContent = 'Sending...';
                
                // Form will submit to FormSubmit API
                // FormSubmit will handle the email sending and redirect
                return true;
            }

            // Check for successful form submission on page load
            function checkFormSubmissionSuccess() {
                // Wait for SweetAlert2 to be available
                if (typeof Swal === 'undefined') {
                    // Retry after a short delay
                    setTimeout(checkFormSubmissionSuccess, 200);
                    return;
                }

                const urlParams = new URLSearchParams(window.location.search);
                const submitted = urlParams.get('submitted');
                
                // Check if form was successfully submitted
                if (submitted === 'true') {
                    // Remove success parameter from URL
                    const cleanUrl = window.location.pathname;
                    window.history.replaceState({}, document.title, cleanUrl);
                    
                    // Show success modal with countdown
                    let countdown = 5;
                    const selectedValue = contactMemberSelect ? contactMemberSelect.value : '';
                    const teamName = selectedValue && teamNames[selectedValue] ? teamNames[selectedValue] : 'the team member';
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Message Sent Successfully!',
                        html: `
                            <div class="text-left">
                                <p class="text-gray-700 mb-3">Your message has been sent to <strong class="text-[#800020]">${teamName}</strong>.</p>
                                <p class="text-sm text-gray-500">This page will refresh in <strong id="countdown" class="text-[#800020]">${countdown}</strong> seconds...</p>
                            </div>
                        `,
                        showConfirmButton: true,
                        confirmButtonText: 'Refresh Now',
                        confirmButtonColor: '#800020',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        timer: countdown * 1000,
                        timerProgressBar: true,
                        didOpen: () => {
                            const countdownElement = document.getElementById('countdown');
                            const timerInterval = setInterval(() => {
                                countdown--;
                                if (countdownElement) {
                                    countdownElement.textContent = countdown;
                                }
                                if (countdown <= 0) {
                                    clearInterval(timerInterval);
                                }
                            }, 1000);
                        }
                    }).then((result) => {
                        // Reload page whether user clicks button or timer expires
                        window.location.reload();
                    });
                }
            }

            // Initialize on page load
            function initContactForm() {
                if (contactMemberSelect && contactForm) {
                    // Set initial state
                    submitBtn.disabled = true;
                    
                    // Listen for dropdown changes
                    contactMemberSelect.addEventListener('change', updateFormAction);
                    
                    // Handle form submission
                    contactForm.addEventListener('submit', handleFormSubmit);
                    
                    // Check for successful form submission
                    checkFormSubmissionSuccess();
                }
            }

            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    initContactForm();
                    // Also check after a short delay to ensure SweetAlert2 is loaded
                    setTimeout(checkFormSubmissionSuccess, 500);
                });
            } else {
                initContactForm();
                setTimeout(checkFormSubmissionSuccess, 500);
            }

            // Make it available globally for SPA navigation re-initialization
            window.initContactForm = initContactForm;
        })();
    </script>
</body>
</html>

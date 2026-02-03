<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Invoice SaaS') }}</title>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div class="min-h-screen bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Header -->
            <div class="text-center mb-16">
                <h1 class="text-6xl font-bold text-white mb-4">Invoice SaaS</h1>
                <p class="text-2xl text-white/90">Multi-Tenant Invoice & Subscription Management</p>
            </div>

            <!-- Feature Cards -->
            <div class="grid md:grid-cols-3 gap-8 mb-16">
                <div class="bg-white/10 backdrop-blur-lg rounded-lg p-8 text-white">
                    <div class="text-4xl mb-4">📄</div>
                    <h3 class="text-xl font-semibold mb-2">Invoice Management</h3>
                    <p class="text-white/80">Create, send, and track invoices with ease. Generate PDFs and send via email.</p>
                </div>

                <div class="bg-white/10 backdrop-blur-lg rounded-lg p-8 text-white">
                    <div class="text-4xl mb-4">🏢</div>
                    <h3 class="text-xl font-semibold mb-2">Multi-Tenant</h3>
                    <p class="text-white/80">Complete data isolation. Each company has secure access to their own data.</p>
                </div>

                <div class="bg-white/10 backdrop-blur-lg rounded-lg p-8 text-white">
                    <div class="text-4xl mb-4">💳</div>
                    <h3 class="text-xl font-semibold mb-2">Subscriptions</h3>
                    <p class="text-white/80">Flexible pricing plans with Free and Pro tiers. Upgrade anytime.</p>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="bg-white rounded-2xl shadow-2xl p-12 text-center">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Get Started Today</h2>
                <p class="text-xl text-gray-600 mb-8">Sign in to access your dashboard</p>

                <div class="flex gap-4 justify-center">
                    <a href="/login" class="px-8 py-4 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition">
                        Login
                    </a>
                    <a href="/register" class="px-8 py-4 bg-gray-200 text-gray-800 rounded-lg font-semibold hover:bg-gray-300 transition">
                        Register
                    </a>
                </div>

                <div class="mt-12 pt-8 border-t border-gray-200">
                    <p class="text-sm text-gray-600 mb-4">Demo Accounts (password: <code class="bg-gray-100 px-2 py-1 rounded">password</code>)</p>
                    <div class="grid md:grid-cols-2 gap-4 max-w-2xl mx-auto text-left">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="font-semibold text-gray-900">Demo Company (Pro Plan)</p>
                            <p class="text-sm text-gray-600">Owner: owner@democompany.com</p>
                            <p class="text-sm text-gray-600">Member: member@democompany.com</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="font-semibold text-gray-900">Test Company (Free Plan)</p>
                            <p class="text-sm text-gray-600">Owner: test@testcompany.com</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tech Stack -->
            <div class="mt-16 text-center text-white/60 text-sm">
                <p class="mb-2">Built with Laravel 10, PHP 8.2, MySQL, Blade, TailwindCSS</p>
                <p>Multi-tenant • Role-based Auth • Queue Jobs • PDF Generation • Email Notifications</p>
            </div>
        </div>
    </div>
</body>
</html>

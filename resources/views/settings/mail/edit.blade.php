@extends('layouts.app')

@section('title', 'SMTP Configuration | Email Settings')

@section('content')
<div class="min-h-screen bg-gray-50">
  <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="bg-white shadow rounded-lg overflow-hidden">
      <!-- Header -->
      <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-blue-600 to-indigo-700">
        <div class="flex items-center justify-between">
          <h1 class="text-2xl font-semibold text-white">Email Server Configuration</h1>
          <div class="flex-shrink-0">
            <svg class="h-8 w-8 text-blue-200" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
              <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
              <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
            </svg>
          </div>
        </div>
        <p class="mt-1 text-sm text-blue-100">Configure your SMTP settings for outgoing emails</p>
      </div>

      <!-- Success Message -->
      @if(session('success'))
        <div class="p-4 bg-green-50 border-l-4 border-green-400">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-green-700">{{ session('success') }}</p>
            </div>
          </div>
        </div>
      @endif

      <!-- Form -->
      <form method="POST" action="{{ route('settings.mail.update') }}" class="divide-y divide-gray-200">
        @csrf
        <div class="px-6 py-5 space-y-6">
          <!-- Server Settings -->
          <div>
            <h2 class="text-lg font-medium text-gray-900 mb-4">SMTP Server Settings</h2>
            <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-6">
              <div class="sm:col-span-3">
                <label for="mail_host" class="block text-sm font-medium text-gray-700">SMTP Host</label>
                <input type="text" name="mail_host" id="mail_host" value="{{ old('mail_host', $settings?->mail_host) }}" 
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
              </div>

              <div class="sm:col-span-3">
                <label for="mail_port" class="block text-sm font-medium text-gray-700">SMTP Port</label>
                <input type="number" name="mail_port" id="mail_port" value="{{ old('mail_port', $settings?->mail_port) }}" 
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
              </div>

              <div class="sm:col-span-3">
                <label for="mail_username" class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" name="mail_username" id="mail_username" value="{{ old('mail_username', $settings?->mail_username) }}" 
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
              </div>

              <div class="sm:col-span-3">
                <label for="mail_password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" name="mail_password" id="mail_password" value="{{ old('mail_password', $settings?->mail_password) }}" 
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
              </div>

              <div class="sm:col-span-3">
                <label for="mail_encryption" class="block text-sm font-medium text-gray-700">Encryption</label>
                <select id="mail_encryption" name="mail_encryption" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                  <option value="" @if(!$settings?->mail_encryption) selected @endif>None</option>
                  <option value="ssl" @if($settings?->mail_encryption=='ssl') selected @endif>SSL</option>
                  <option value="tls" @if($settings?->mail_encryption=='tls') selected @endif>TLS</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Sender Information -->
          <div class="pt-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Sender Information</h2>
            <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-6">
              <div class="sm:col-span-3">
                <label for="from_address" class="block text-sm font-medium text-gray-700">From Address</label>
                <input type="email" name="from_address" id="from_address" value="{{ old('from_address', $settings?->from_address) }}" 
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
              </div>

              <div class="sm:col-span-3">
                <label for="from_name" class="block text-sm font-medium text-gray-700">From Name</label>
                <input type="text" name="from_name" id="from_name" value="{{ old('from_name', $settings?->from_name) }}" 
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
              </div>
            </div>
          </div>
        </div>

        <!-- Form Footer -->
        <div class="px-6 py-4 bg-gray-50 text-right">
          <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Save Settings
          </button>
        </div>
      </form>
    </div>

    <!-- Test Connection Card -->
    <div class="mt-6 bg-white shadow rounded-lg overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200">
        <h2 class="text-lg font-medium text-gray-900">Test Email Configuration</h2>
      </div>
      <div class="px-6 py-5">
        <p class="text-sm text-gray-600 mb-4">Send a test email to verify your SMTP settings are configured correctly.</p>
        <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
          <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd" />
          </svg>
          Send Test Email
        </button>
      </div>
    </div>
  </div>
</div>
@endsection
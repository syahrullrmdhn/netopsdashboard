<x-guest-layout>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100">
    <div class="max-w-md w-full bg-white p-10 rounded-xl shadow-2xl">
      <div class="text-center mb-8">
        <!-- Icon + Title -->
        <svg xmlns="http://www.w3.org/2000/svg"
             class="h-12 w-12 mx-auto text-indigo-600"
             fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2
                   M7 19h10a2 2 0 002-2V7a2 2 0
                   00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
        <h1 class="text-3xl font-bold text-gray-800 mt-4">Network Ops Dashboard</h1>
        <p class="text-gray-500 mt-2">Sign in to your account</p>
      </div>
      
      <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Email -->
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <!-- envelope icon -->
              <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg"
                   viewBox="0 0 20 20" fill="currentColor">
                <path d="M2.003 5.884L10 9.882l7.997-3.998A2
                         2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                <path d="M18 8.118l-8 4-8-4V14a2 2 0
                         002 2h12a2 2 0 002-2V8.118z" />
              </svg>
            </div>
            <input id="email" name="email" type="email" value="{{ old('email') }}"
                   required autofocus
                   class="pl-10 bg-gray-50 border border-gray-200 text-gray-900 text-sm
                          rounded-lg focus:ring-indigo-500 focus:border-indigo-500
                          block w-full p-2.5"
                   placeholder="you@example.com"/>
          </div>
          @error('email')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
          @enderror
        </div>

        <!-- Password -->
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <!-- lock icon -->
              <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg"
                   viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                      d="M5 9V7a5 5 0 0110 0v2a2
                         2 0 012 2v5a2 2 0 01-2 2H5a2
                         2 0 01-2-2v-5a2 2 0
                         012-2zm8-2v2H7V7a3 3 0 016 0z"
                      clip-rule="evenodd" />
              </svg>
            </div>
            <input id="password" name="password" type="password" required
                   class="pl-10 bg-gray-50 border border-gray-200 text-gray-900 text-sm
                          rounded-lg focus:ring-indigo-500 focus:border-indigo-500
                          block w-full p-2.5"
                   placeholder="••••••••"/>
          </div>
          @error('password')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
          @enderror
        </div>

        <!-- Remember & Forgot -->
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input id="remember_me" name="remember" type="checkbox"
                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
            <label for="remember_me" class="ml-2 block text-sm text-gray-700">Remember me</label>
          </div>
          <div class="text-sm">
            @if (Route::has('password.request'))
              <a href="{{ route('password.request') }}"
                 class="font-medium text-indigo-600 hover:text-indigo-500">
                Forgot password?
              </a>
            @endif
          </div>
        </div>

        <!-- Submit -->
        <button type="submit"
          class="w-full flex justify-center py-3 px-4 border border-transparent
                 rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600
                 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2
                 focus:ring-indigo-500 transition duration-150 ease-in-out">
          Sign in
        </button>
      </form>

      <!-- Divider & Register -->
      <div class="mt-6">
        <div class="relative">
          <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-300"></div>
          </div>
          <div class="relative flex justify-center text-sm">
            <span class="px-2 bg-white text-gray-500">New to Network Ops?</span>
          </div>
        </div>
        <div class="mt-4">
          <a href="{{ route('register') }}"
             class="w-full flex justify-center py-2.5 px-4 border border-gray-300
                    rounded-lg shadow-sm text-sm font-medium text-gray-700
                    hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2
                    focus:ring-indigo-500 transition duration-150 ease-in-out">
            Create an account
          </a>
        </div>
      </div>
    </div>
  </div>
</x-guest-layout>

@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Dobrodošli na Dashboard!</h2>
                <p class="text-gray-600 mb-6">
                    Uspešno ste se prijavili u KPRM aplikaciju.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-blue-50 p-6 rounded-lg">
                        <h3 class="text-lg font-semibold text-blue-900 mb-2">Korisnik</h3>
                        <p class="text-blue-700">{{ auth()->user()->name }}</p>
                    </div>
                    <div class="bg-green-50 p-6 rounded-lg">
                        <h3 class="text-lg font-semibold text-green-900 mb-2">Email</h3>
                        <p class="text-green-700">{{ auth()->user()->email }}</p>
                    </div>
                    <div class="bg-purple-50 p-6 rounded-lg">
                        <h3 class="text-lg font-semibold text-purple-900 mb-2">Status</h3>
                        <p class="text-purple-700">Aktivan</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

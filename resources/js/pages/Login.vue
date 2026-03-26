<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const password = ref('');
const showPassword = ref(false);

const togglePassword = () => {
    showPassword.value = !showPassword.value;
};

const handleLogin = () => {
    router.get('/prototype');
    // Implement your login logic here
};
</script>

<template>
    <div class="row g-0 justify-content-center align-items-center vh-100">
        <div class="col-md-6 d-flex flex-column align-items-center justify-content-center h-100 bg-white">
            <div class="mb-4 text-center">
                <img src="/assets/svg/JTE_Logo_blk_txt.svg" alt="JTIS Logo" style="width: 150px;">
            </div>
            
            <div class="w-75 ps-5 pe-5 pb-5" style="min-width: 320px;">
                <h1 class="text-center mb-2">WELCOME TO JTIS</h1>
                <p class="text-justify text-muted mb-4">Untuk Menggunakan Sistem Ini harap Login Menggunakan Email dan Password Anda</p>
                
                <div class="input-group mb-3 border rounded custom-group">
                    <span class="input-group-text bg-transparent border-0 pe-0">
                        <svg style="width: 24px; height: 24px;" viewBox="0 0 40 33" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M37.8333 5.58333C37.8333 3.6125 36.2208 2 34.25 2H5.58333C3.6125 2 2 3.6125 2 5.58333M37.8333 5.58333V27.0833C37.8333 29.0542 36.2208 30.6667 34.25 30.6667H5.58333C3.6125 30.6667 2 29.0542 2 27.0833V5.58333M37.8333 5.58333L19.9167 18.125L2 5.58333" stroke="#1E1E1E" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div class="form-floating flex-grow-1">
                        <input type="email" class="form-control border-0 shadow-none bg-transparent" id="emailInput" placeholder="name@example.com">
                        <label for="emailInput" class="text-muted">E-mail</label>
                    </div>
                </div>

                <div class="input-group mb-4 border rounded custom-group">
                    <span class="input-group-text bg-transparent border-0 pe-0">
                        <svg style="width: 24px; height: 24px;" viewBox="0 0 37 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9.16667 18.125V10.9583C9.16667 8.58244 10.1105 6.30385 11.7905 4.62384C13.4705 2.94382 15.7491 2 18.125 2C20.5009 2 22.7795 2.94382 24.4595 4.62384C26.1395 6.30385 27.0833 8.58244 27.0833 10.9583V18.125M5.58333 18.125H30.6667C32.6457 18.125 34.25 19.7293 34.25 21.7083V34.25C34.25 36.229 32.6457 37.8333 30.6667 37.8333H5.58333C3.60431 37.8333 2 36.229 2 34.25V21.7083C2 19.7293 3.60431 18.125 5.58333 18.125Z" stroke="#1E1E1E" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div class="form-floating flex-grow-1">
                        <input :type="showPassword ? 'text' : 'password'" 
                               v-model="password"
                               class="form-control border-0 shadow-none bg-transparent" 
                               id="passInput" 
                               placeholder="Password">
                        <label for="passInput" class="text-muted">Password</label>
                    </div>
                    <button class="btn border-0 bg-transparent" type="button" @click="togglePassword">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="text-muted" viewBox="0 0 16 16">
                            <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
                            <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
                            <line v-if="!showPassword" x1="2" y1="2" x2="14" y2="14" stroke="currentColor" stroke-width="1.5" />
                        </svg>
                    </button>
                </div>

                <button class="btn btn-primary w-100 py-2 shadow-sm" style="background-color: #1E293B; border: none;" @click="handleLogin">Login !</button>
            </div>
        </div>

        <div class="col-md-6 d-none d-md-block p-0">
            <img src="/assets/images/MANDOSI-2-scaled.jpeg" alt="Login Image" class="w-100 vh-100" style="object-fit: cover;">
        </div>
    </div>
</template>
<script lang="js">
    //  import { useAuthStore } from '@/stores/auth'; 
    //  export default {
    //      setup() {
    //          const authStore = useAuthStore();
    //          return { authStore };
    //      },
    //  };
</script>
<template>
    <div class="container text-center vh-100 d-flex align-items-center justify-content-center">
        <div class="row justify-content-center">
            <div class="col">
                <div class="container">
                    <h1>WELCOME TO JTIS</h1>
                    <p class="">Untuk Menggunakan Sistem Ini harap Login Menggunakan Email dan Password Anda</p>
                    <div class="input-group mb-3 custom-input-group active-group">
                        <span class="input-group-text bg-transparent border-0 pe-0">
                            <i class="bi bi-envelope fs-4"></i> 
                        </span>
                            <div class="form-floating flex-grow-1">
                                <input type="email" class="form-control border-0 shadow-none bg-transparent" id="emailInput" placeholder="name@example.com" value="XXXXXXXXXX@GMAIL.COM">
                                <label for="emailInput" class="text-muted">E-mail</label>
                            </div>
                        </div>

                        <div class="input-group mb-3 custom-input-group">
                            <span class="input-group-text bg-transparent border-0 pe-0">
                                <i class="bi bi-lock fs-4"></i>
                            </span>
                            <div class="form-floating flex-grow-1">
                                <input type="password" class="form-control border-0 shadow-none bg-transparent" id="passInput" placeholder="Password" value="password123">
                                <label for="passInput" class="text-muted">Password</label>
                            </div>
                        </div>
                    </div>
                </div>
            <div class="col">
                <div class="container d-flex align-items-center justify-content-center h-100">
                    <img src="resources\assets\images\MANDOSI-2-scaled.jpeg" alt="Login Image" class="img-fluid">
                </div>
            </div>
        </div>
    </div>
</template>
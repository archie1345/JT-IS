<script setup lang="ts">
import { ref } from 'vue'

const count = ref(0)
const message = ref('Hello from Vue!')
const isActive = ref(false)
const selectedTab = ref('home')

const tabs = [
  { id: 'home', label: 'Home' },
  { id: 'profile', label: 'Profile' },
  { id: 'settings', label: 'Settings' }
]

const increment = () => {
  count.value++
}

const toggleActive = () => {
  isActive.value = !isActive.value
}
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Bootstrap Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-6">
      <div class="container">
        <a class="navbar-brand" href="#">Vue + Tailwind + Bootstrap</a>
        <button 
          class="navbar-toggler" 
          type="button" 
          data-bs-toggle="collapse" 
          data-bs-target="#navbarNav"
          aria-controls="navbarNav" 
          aria-expanded="false" 
          aria-label="Toggle navigation"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto">
            <li class="nav-item">
              <a class="nav-link active" href="#">Home</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#">Features</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#">Pricing</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <div class="container">
      <!-- Bootstrap Card with Tailwind classes -->
      <div class="card shadow-lg mb-6">
        <div class="card-header bg-white border-bottom">
          <h3 class="card-title mb-0">Welcome to Vue 3</h3>
        </div>
        <div class="card-body">
          <p class="text-muted mb-4">{{ message }}</p>
          
          <!-- Bootstrap Button with Tailwind hover effects -->
          <button 
            class="btn btn-primary me-2"
            @click="increment"
          >
            Count: {{ count }}
          </button>
          
          <button 
            class="btn btn-outline-secondary"
            @click="toggleActive"
          >
            Toggle Active
          </button>
        </div>
      </div>

      <!-- Bootstrap Tabs with Tailwind styling -->
      <div class="card shadow mb-6">
        <div class="card-header bg-white border-bottom">
          <ul class="nav nav-tabs card-header-tabs">
            <li 
              v-for="tab in tabs" 
              :key="tab.id"
              class="nav-item"
            >
              <a 
                class="nav-link"
                :class="{ active: selectedTab === tab.id }"
                href="#"
                @click.prevent="selectedTab = tab.id"
              >
                {{ tab.label }}
              </a>
            </li>
          </ul>
        </div>
        <div class="card-body">
          <div 
            v-show="selectedTab === 'home'"
            class="tab-content"
          >
            <h4>Home Tab</h4>
            <p class="text-muted">
              This is the home tab content. You can use Tailwind utility classes 
              like <code>text-muted</code>, <code>mb-4</code>, and <code>text-center</code> 
              alongside Bootstrap components.
            </p>
          </div>
          <div 
            v-show="selectedTab === 'profile'"
            class="tab-content"
          >
            <h4>Profile Tab</h4>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group mb-3">
                  <label class="form-label">Name</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    placeholder="Enter your name"
                  >
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group mb-3">
                  <label class="form-label">Email</label>
                  <input 
                    type="email" 
                    class="form-control" 
                    placeholder="Enter your email"
                  >
                </div>
              </div>
            </div>
          </div>
          <div 
            v-show="selectedTab === 'settings'"
            class="tab-content"
          >
            <h4>Settings Tab</h4>
            <div class="form-check form-switch mb-3">
              <input 
                class="form-check-input" 
                type="checkbox" 
                id="flexSwitchCheckChecked"
                checked
              >
              <label class="form-check-label" for="flexSwitchCheckChecked">
                Enable notifications
              </label>
            </div>
            <div class="form-check form-switch mb-3">
              <input 
                class="form-check-input" 
                type="checkbox" 
                id="flexSwitchCheckChecked2"
              >
              <label class="form-check-label" for="flexSwitchCheckChecked2">
                Dark mode
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Bootstrap Alert with Tailwind spacing -->
      <div 
        class="alert alert-success mb-4"
        :class="{ 'd-none': !isActive }"
      >
        <strong>Success!</strong> The active state is now {{ isActive ? 'enabled' : 'disabled' }}.
      </div>

      <!-- Bootstrap Grid with Tailwind flex utilities -->
      <div class="row">
        <div class="col-md-4 mb-4">
          <div class="card h-100">
            <div class="card-body">
              <h5 class="card-title">Card 1</h5>
              <p class="card-text">
                This card uses Bootstrap grid system with Tailwind spacing classes.
              </p>
              <button class="btn btn-sm btn-outline-primary">
                Learn More
              </button>
            </div>
          </div>
        </div>
        <div class="col-md-4 mb-4">
          <div class="card h-100">
            <div class="card-body">
              <h5 class="card-title">Card 2</h5>
              <p class="card-text">
                Bootstrap's responsive grid combined with Tailwind's utility classes.
              </p>
              <button class="btn btn-sm btn-outline-primary">
                Learn More
              </button>
            </div>
          </div>
        </div>
        <div class="col-md-4 mb-4">
          <div class="card h-100">
            <div class="card-body">
              <h5 class="card-title">Card 3</h5>
              <p class="card-text">
                Perfect for building complex layouts with both frameworks.
              </p>
              <button class="btn btn-sm btn-outline-primary">
                Learn More
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
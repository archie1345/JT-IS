<script setup>
import { ref, computed } from 'vue';

// Mock data
const users = ref([
    { id: 1, name: 'John Smith', email: 'john.smith@gmail.com', username: 'jonny77', status: 'Active', role: 'Admin', joined: 'March 12, 2023', lastActive: '1 minute ago' },
    { id: 2, name: 'Olivia Bennett', email: 'ollyben@gmail.com', username: 'olly659', status: 'Inactive', role: 'User', joined: 'June 27, 2022', lastActive: '1 month ago' },
    { id: 3, name: 'Daniel Warren', email: 'dwarren3@gmail.com', username: 'dwarren3', status: 'Active', role: 'User', joined: 'January 8, 2024', lastActive: '4 days ago' },
    { id: 4, name: 'Chloe Hayes', email: 'chloehhye@gmail.com', username: 'chloehh', status: 'Active', role: 'Guest', joined: 'October 5, 2021', lastActive: '10 days ago' },
    { id: 5, name: 'Marcus Reed', email: 'reeds777@gmail.com', username: 'reeds7', status: 'Inactive', role: 'User', joined: 'February 19, 2023', lastActive: '3 months ago' },
    { id: 6, name: 'Isabelle Clark', email: 'belleclark@gmail.com', username: 'bellecl', status: 'Active', role: 'Moderator', joined: 'August 30, 2022', lastActive: '1 week ago' },
]);

// Filtering & Sorting State
const searchQuery = ref('');
const selectedRole = ref('');
const selectedStatus = ref('');
const sortColumn = ref('name');
const sortAscending = ref(true);

// Modal & Form State
const showModal = ref(false);
const isEditing = ref(false);

// We use one object for both adding and editing
const formUser = ref({ 
    id: null, 
    name: '', 
    email: '', 
    username: '', 
    password: '', // Kept blank unless typing a new one
    role: 'User', 
    status: 'Active' 
});

// --- METHODS ---

const sortBy = (column) => {
    if (sortColumn.value === column) {
        sortAscending.value = !sortAscending.value;
    } else {
        sortColumn.value = column;
        sortAscending.value = true;
    }
};

// Open Modal to ADD a new user
const openAddModal = () => {
    isEditing.value = false;
    formUser.value = { id: null, name: '', email: '', username: '', password: '', role: 'User', status: 'Active' };
    showModal.value = true;
};

// Open Modal to EDIT an existing user
const openEditModal = (user) => {
    isEditing.value = true;
    // Spread operator (...) creates a copy so we don't edit the table directly until "Save" is clicked.
    // We intentionally leave password blank. 
    formUser.value = { ...user, password: '' }; 
    showModal.value = true;
};

// Save User (Handles both Add and Update)
const saveUser = () => {
    if (isEditing.value) {
        // Find user and update their data
        const index = users.value.findIndex(u => u.id === formUser.value.id);
        if (index !== -1) {
            // We only update the password in our mock data if they typed a new one
            const updatedData = { ...formUser.value };
            if (!updatedData.password) {
                delete updatedData.password; // Don't overwrite with a blank password
            }
            // Merge updated data into existing user
            users.value[index] = { ...users.value[index], ...updatedData };
        }
    } else {
        // Add new user
        users.value.push({
            id: Date.now(),
            ...formUser.value,
            joined: new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }),
            lastActive: 'Just now',
            avatar: `https://ui-avatars.com/api/?name=${formUser.value.name.replace(' ', '+')}&background=random`
        });
    }
    showModal.value = false;
};

// Delete a user
const deleteUser = (id) => {
    if (confirm("Are you sure you want to delete this user?")) {
        users.value = users.value.filter(user => user.id !== id);
    }
};

// --- COMPUTED PROPERTIES ---

const filteredAndSortedUsers = computed(() => {
    let result = users.value.filter(user => {
        const matchesSearch = user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                              user.email.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesRole = selectedRole.value === '' || user.role === selectedRole.value;
        const matchesStatus = selectedStatus.value === '' || user.status === selectedStatus.value;
        return matchesSearch && matchesRole && matchesStatus;
    });

    result.sort((a, b) => {
        // Handle sorting if the field might be undefined (like our mock passwords)
        let valA = (a[sortColumn.value] || '').toLowerCase();
        let valB = (b[sortColumn.value] || '').toLowerCase();

        if (valA < valB) return sortAscending.value ? -1 : 1;
        if (valA > valB) return sortAscending.value ? 1 : -1;
        return 0;
    });

    return result;
});
</script>

<template>
    <div class="container-fluid p-4" style="background-color: #F8FAFC; min-height: 100vh;">
        
        <div class="mb-4">
            <h2 class="fw-bold" style="color: #1E293B;">User Management</h2>
            <p class="text-muted mb-0">Manage all users in one place. Control access, assign roles, and monitor activity across your platform.</p>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
            <div class="d-flex gap-2 flex-wrap">
                <div class="input-group bg-white rounded shadow-sm border" style="width: 250px;">
                    <span class="input-group-text bg-transparent border-0 pe-1 text-muted">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                    </span>
                    <input type="text" v-model="searchQuery" class="form-control border-0 shadow-none" placeholder="Search by name or email">
                </div>

                <select v-model="selectedRole" class="form-select bg-white shadow-sm border w-auto text-muted">
                    <option value="">All Roles</option>
                    <option value="Admin">Admin</option>
                    <option value="User">User</option>
                    <option value="Guest">Guest</option>
                    <option value="Moderator">Moderator</option>
                </select>

                <select v-model="selectedStatus" class="form-select bg-white shadow-sm border w-auto text-muted">
                    <option value="">All Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <button @click="openAddModal" class="btn text-white px-4 shadow-sm rounded-pill" style="background-color: #334155;">
                + Add User
            </button>
        </div>

        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle" style="font-size: 0.9rem;">
                    <thead style="background-color: #334155; color: #F8FAFC;">
                        <tr>
                            <th scope="col" class="py-3 px-3 border-0"><input class="form-check-input" type="checkbox"></th>
                            <th scope="col" class="py-3 px-3 border-0 fw-semibold cursor-pointer" @click="sortBy('name')">
                                Full Name <span v-if="sortColumn === 'name'">{{ sortAscending ? '↑' : '↓' }}</span><span v-else>↕</span>
                            </th>
                            <th scope="col" class="py-3 px-3 border-0 fw-semibold cursor-pointer" @click="sortBy('email')">
                                Email <span v-if="sortColumn === 'email'">{{ sortAscending ? '↑' : '↓' }}</span><span v-else>↕</span>
                            </th>
                            <th scope="col" class="py-3 px-3 border-0 fw-semibold cursor-pointer" @click="sortBy('username')">
                                Username <span v-if="sortColumn === 'username'">{{ sortAscending ? '↑' : '↓' }}</span><span v-else>↕</span>
                            </th>
                            <th scope="col" class="py-3 px-3 border-0 fw-semibold text-center">
                                Password
                            </th>
                            <th scope="col" class="py-3 px-3 border-0 fw-semibold cursor-pointer" @click="sortBy('status')">
                                Status <span v-if="sortColumn === 'status'">{{ sortAscending ? '↑' : '↓' }}</span><span v-else>↕</span>
                            </th>
                            <th scope="col" class="py-3 px-3 border-0 fw-semibold cursor-pointer" @click="sortBy('role')">
                                Role <span v-if="sortColumn === 'role'">{{ sortAscending ? '↑' : '↓' }}</span><span v-else>↕</span>
                            </th>
                            <th scope="col" class="py-3 px-3 border-0 fw-semibold">Joined Date</th>
                            <th scope="col" class="py-3 px-3 border-0 fw-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <tr v-for="user in filteredAndSortedUsers" :key="user.id">
                            <td class="px-3 py-2 text-muted"><input class="form-check-input" type="checkbox"></td>
                            <td class="px-3 py-2 text-dark">
                                <div class="d-flex align-items-center gap-2">
                                    {{ user.name }}
                                </div>
                            </td>
                            <td class="px-3 py-2" style="color: #64748B;">{{ user.email }}</td>
                            <td class="px-3 py-2" style="color: #64748B;">{{ user.username }}</td>
                            
                            <td class="px-3 py-2 text-center" style="color: #94A3B8; letter-spacing: 2px;">
                                &bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;
                            </td>

                            <td class="px-3 py-2">
                                <span class="badge rounded-pill px-3 py-2" :class="user.status === 'Active' ? 'bg-success bg-opacity-75' : 'bg-secondary bg-opacity-50 text-dark'">
                                    {{ user.status }}
                                </span>
                            </td>
                            <td class="px-3 py-2" style="color: #64748B;">{{ user.role }}</td>
                            <td class="px-3 py-2" style="color: #64748B;">{{ user.joined }}</td>
                            
                            <td class="px-3 py-2 text-center">
                                <button @click="openEditModal(user)" class="btn btn-sm btn-link text-muted p-1">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/></svg>
                                </button>
                                <button @click="deleteUser(user.id)" class="btn btn-sm btn-link text-danger p-1">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="filteredAndSortedUsers.length === 0">
                            <td colspan="10" class="text-center py-4 text-muted">No users found matching your criteria.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap text-muted small">
            <div>Showing {{ filteredAndSortedUsers.length }} rows</div>
        </div>

        <div v-if="showModal" class="modal-backdrop fade show"></div>
        <div v-if="showModal" class="modal fade show d-block" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold">{{ isEditing ? 'Edit User' : 'Add New User' }}</h5>
                        <button type="button" class="btn-close shadow-none" @click="showModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="saveUser">
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">Full Name</label>
                                <input type="text" v-model="formUser.name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">Email</label>
                                <input type="email" v-model="formUser.email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">Username</label>
                                <input type="text" v-model="formUser.username" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">
                                    Password <span v-if="isEditing" class="fw-normal text-secondary">(Leave blank to keep current)</span>
                                </label>
                                <input type="password" 
                                       v-model="formUser.password" 
                                       class="form-control" 
                                       :placeholder="isEditing ? '••••••••' : 'Enter new password'" 
                                       :required="!isEditing">
                            </div>

                            <div class="row mb-4">
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold">Role</label>
                                    <select v-model="formUser.role" class="form-select">
                                        <option>Admin</option>
                                        <option>User</option>
                                        <option>Guest</option>
                                        <option>Moderator</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold">Status</label>
                                    <select v-model="formUser.status" class="form-select">
                                        <option>Active</option>
                                        <option>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn w-100 text-white" style="background-color: #334155;">
                                {{ isEditing ? 'Update User' : 'Save New User' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
.form-select:focus, .form-control:focus {
    border-color: #94A3B8;
    box-shadow: 0 0 0 0.1rem rgba(148, 163, 184, 0.25);
}
.table > :not(caption) > * > * {
    border-bottom-color: #F1F5F9;
}
.cursor-pointer {
    cursor: pointer;
    user-select: none;
}
.cursor-pointer:hover {
    color: #94A3B8 !important;
}
</style>
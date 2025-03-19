<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Pencil, Trash, Check, RefreshCw } from 'lucide-vue-next'
import Swal from 'sweetalert2'


const props = defineProps(['todos'])
const newTodo = ref('')
const showModal = ref(false)
const editingTodo = ref(null)
const editedTitle = ref('')
const selectedTodos = ref([]) // List ID todo yang dipilih
const batchUpdates = ref([]) // List todo yang diubah
const isProcessing = ref(false)

// Computed property untuk select all
const allSelected = computed({
    get: () => {
        return props.todos.length > 0 && selectedTodos.value.length === props.todos.length
    },
    set: (value) => {
        selectedTodos.value = value ? props.todos.map(t => t.id) : []
    }
})

// Jika todo di select maka akan muncul button
const hasSelection = computed(() => selectedTodos.value.length > 0);

// Tambah todo baru
const addTodo = () => {
    if (!newTodo.value.trim()) return
    router.post('/todos', { title: newTodo.value }, {
        onFinish: () => {
            newTodo.value = ''
            Swal.fire('Sukses!', 'Todo berhasil ditambahkan.', 'success')
        }
    })
}

// Buka modal edit
const openEditModal = (todo) => {
    editingTodo.value = todo
    editedTitle.value = todo.title
    showModal.value = true
}

// Update todo
const updateTodo = () => {
    if (!editedTitle.value.trim()) return
    router.put(`/todos/${editingTodo.value.id}`, { title: editedTitle.value }, {
        onFinish: () => {
            showModal.value = false
            newTodo.value = ''
            Swal.fire('Sukses!', 'Todo berhasil diupdate.', 'success')
        }
    })
}

// Hapus satu todo
const deleteTodo = (id) => {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: 'Todo ini akan dihapus secara permanen!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/todos/${id}`, {
                onFinish: () => {
                    Swal.fire('Dihapus!', 'Todo berhasil dihapus.', 'success')
                }
            })
        }
    })
}

// Simpan perubahan edit langsung dalam list
const trackUpdate = (todo) => {
    const existing = batchUpdates.value.find(t => t.id === todo.id)
    if (existing) {
        existing.title = todo.title
    } else {
        batchUpdates.value.push({ id: todo.id, title: todo.title })
    }
}

// Batch update
const batchUpdate = () => {
    if (selectedTodos.value.length === 0) {
        alert('Pilih minimal satu item untuk diupdate!')
        return
    }
    
    // Filter updates hanya untuk todo yang dipilih
    const updates = batchUpdates.value.filter(update => 
        selectedTodos.value.includes(update.id)
    )
    
    if (updates.length === 0) {
        alert('Tidak ada perubahan pada item yang dipilih!')
        return
    }
    
    isProcessing.value = true
    router.post('/todos/batch-update-delete', { updates, deletes: [] }, {
        onFinish: () => {
            batchUpdates.value = batchUpdates.value.filter(update => 
                !selectedTodos.value.includes(update.id)
            )
            selectedTodos.value = []
            isProcessing.value = false
            Swal.fire('Sukses!', 'Todo berhasil diperbarui.', 'success')
        }
    })
}

// Batch delete
const batchDelete = () => {
    if (selectedTodos.value.length === 0) {
        Swal.fire('Oops!', 'Pilih minimal satu item untuk dihapus!', 'warning')
        return
    }

    Swal.fire({
        title: `Hapus ${selectedTodos.value.length} item?`,
        text: 'Aksi ini tidak dapat dibatalkan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            isProcessing.value = true
            router.post('/todos/batch-update-delete', { updates: [], deletes: selectedTodos.value }, {
                onFinish: () => {
                    selectedTodos.value = []
                    isProcessing.value = false
                    Swal.fire('Dihapus!', 'Item yang dipilih telah dihapus.', 'success')
                }
            })
        }
    })
}

// Fungsi untuk mengetahui apakah todo telah diubah
const isModified = (todo) => {
    return batchUpdates.value.some(t => t.id === todo.id)
}
</script>

<template>
    <div class="max-w-lg mx-auto mt-10 bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-xl font-bold mb-4">To-Do List</h1>

        <!-- Tambah Todo -->
        <div class="flex mb-4">
            <input v-model="newTodo" placeholder="Tambah tugas baru" class="border p-2 w-full rounded-md" />
            <button @click="addTodo" class="bg-blue-500 text-white px-4 py-2 rounded-md ml-2">
                Add
            </button>
        </div>

        <!-- Controls -->
        <div class="flex justify-between mb-2">
            <div class="flex items-center">
                <input type="checkbox" v-model="allSelected" id="select-all" class="mr-2">
                <label for="select-all">Select All</label>
            </div>
            <div class="text-sm text-gray-500">
                {{ selectedTodos.length }} dari {{ props.todos.length }} dipilih
            </div>
        </div>

        <!-- List Todo -->
        <ul v-if="props.todos.length">
            <li v-for="todo in props.todos" :key="todo.id" 
                class="flex justify-between items-center border-b py-2"
                :class="{ 'bg-blue-50': selectedTodos.includes(todo.id) }">
                <div class="flex items-center w-full">
                    <input type="checkbox" v-model="selectedTodos" :value="todo.id" class="mr-2">
                    <input type="text" v-model="todo.title" @input="trackUpdate(todo)" 
                        class="border p-1 rounded-md flex-grow"
                        :class="{ 'border-yellow-500': isModified(todo) }">
                    <div class="flex space-x-1 ml-2">
                        <button @click="openEditModal(todo)" class="bg-yellow-400 text-white p-1 rounded-sm flex items-center justify-center text-xs">
                            <Pencil size="16" />
                        </button>
                        <button @click="deleteTodo(todo.id)" class="bg-red-500 text-white p-1 rounded-sm flex items-center justify-center text-xs">
                            <Trash size="16" />
                        </button>
                    </div>
                </div>
            </li>
        </ul>
        <p v-else class="text-gray-500">Tidak ada tugas.</p>

        <!-- Tombol Batch Action -->
        <div v-if="hasSelection" class="grid grid-cols-2 gap-2 mt-4">
            <button @click="batchUpdate" 
                class="bg-green-500 text-white px-4 py-2 rounded-md flex items-center justify-center"
                :disabled="isProcessing">
                <Check size="16" class="mr-1" />
                Update Selected
            </button>
            <button @click="batchDelete" 
                class="bg-red-500 text-white px-4 py-2 rounded-md flex items-center justify-center"
                :disabled="isProcessing">
                <Trash size="16" class="mr-1" />
                Delete Selected
            </button>
        </div>
    </div>

    <!-- Modal Edit -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center">
        <div class="bg-white p-6 rounded-lg shadow-lg w-96">
            <h2 class="text-lg font-semibold mb-4">Edit Title</h2>
            <input v-model="editedTitle" class="w-full border p-2 rounded-md" />
            <div class="flex justify-end mt-4">
                <button @click="showModal = false" class="bg-gray-400 text-white px-4 py-2 rounded-md mr-2">
                    Cancel
                </button>
                <button @click="updateTodo" class="bg-blue-500 text-white px-4 py-2 rounded-md">
                    Update
                </button>
            </div>
        </div>
    </div>
</template>

<style>
.line-through {
    text-decoration: line-through;
    color: gray;
}
</style>
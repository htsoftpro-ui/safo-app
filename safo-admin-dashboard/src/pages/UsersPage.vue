<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'

const store = useUserStore()
const router = useRouter()
const search = ref('')
const typeFilter = ref('')

onMounted(() => store.fetchUsers())

function applyFilters() {
  const params: Record<string, any> = {}
  if (search.value) params.search = search.value
  if (typeFilter.value) params.type = typeFilter.value
  store.fetchUsers(params)
}

async function handleToggleStatus(id: number) {
  await store.toggleStatus(id)
}

async function handleVerifySupplier(id: number) {
  await store.verifySupplier(id)
  store.fetchUsers()
}

async function handleDelete(id: number) {
  if (!confirm('هل أنت متأكد من حذف هذا المستخدم؟')) return
  const result = await store.deleteUser(id)
  if (!result.success) alert(result.message || 'فشل الحذف')
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold">المستخدمون</h2>
      <span class="text-sm text-gray-500">{{ store.total }} مستخدم</span>
    </div>

    <div class="card mb-6">
      <div class="flex flex-wrap gap-4">
        <input v-model="search" type="text" class="input-field flex-1 min-w-[200px]" placeholder="بحث بالاسم أو الهاتف..."
               @keyup.enter="applyFilters" />
        <select v-model="typeFilter" class="input-field w-auto" @change="applyFilters">
          <option value="">الكل</option>
          <option value="customer">عملاء</option>
          <option value="supplier">موردون</option>
          <option value="admin">مديرون</option>
        </select>
        <button @click="applyFilters" class="btn-primary">بحث</button>
      </div>
    </div>

    <div class="card">
      <div v-if="store.loading" class="text-center py-8 text-gray-500">جاري التحميل...</div>
      <div v-else-if="store.users.length === 0" class="text-center py-8 text-gray-500">لا يوجد مستخدمون</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-right text-gray-500 border-b">
              <th class="py-2">الاسم</th>
              <th class="py-2">الهاتف</th>
              <th class="py-2">النوع</th>
              <th class="py-2">الحالة</th>
              <th class="py-2">التوثيق</th>
              <th class="py-2">إجراءات</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in store.users" :key="user.id" class="border-b hover:bg-gray-50">
              <td class="py-3">
                <div>
                  <p class="font-medium">{{ user.name }}</p>
                  <p class="text-xs text-gray-400">{{ user.store_name || user.city || '-' }}</p>
                </div>
              </td>
              <td class="py-3">{{ user.phone }}</td>
              <td class="py-3">
                <span :class="['badge', user.type === 'admin' ? 'bg-red-100 text-red-700' : user.type === 'supplier' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700']">
                  {{ user.type === 'admin' ? 'مدير' : user.type === 'supplier' ? 'مورد' : 'عميل' }}
                </span>
              </td>
              <td class="py-3">
                <button @click="handleToggleStatus(user.id)" :class="user.is_active ? 'text-green-600' : 'text-red-600'" class="hover:underline">
                  {{ user.is_active ? 'نشط' : 'معطل' }}
                </button>
              </td>
              <td class="py-3">
                <button v-if="user.type === 'supplier'" @click="handleVerifySupplier(user.id)"
                        :class="user.is_verified ? 'text-green-600' : 'text-yellow-600'" class="hover:underline">
                  {{ user.is_verified ? 'موثق' : 'غير موثق' }}
                </button>
                <span v-else class="text-gray-400">-</span>
              </td>
              <td class="py-3">
                <div class="flex gap-2">
                  <button @click="router.push(`/users/${user.id}`)" class="text-blue-600 hover:underline text-sm">تفاصيل</button>
                  <button v-if="user.type !== 'admin'" @click="handleDelete(user.id)" class="text-red-600 hover:underline text-sm">حذف</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

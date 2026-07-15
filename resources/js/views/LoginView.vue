<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const cart = useCartStore()

const form = reactive({ email: '', password: '' })
const errors = ref({})
const submitting = ref(false)
const generalError = ref('')

async function submit() {
    submitting.value = true
    errors.value = {}
    generalError.value = ''
    try {
        await auth.login(form)
        await cart.fetch()
        router.push(route.query.redirect || '/')
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors || {}
            generalError.value = e.response.data.message || ''
        } else {
            generalError.value = 'Terjadi kesalahan. Silakan coba lagi.'
        }
    } finally {
        submitting.value = false
    }
}
</script>

<template>
    <div class="container auth-page">
        <div class="auth-card card">
            <p class="eyebrow">Masuk</p>
            <h1 class="auth-card__title">Selamat datang kembali</h1>

            <p v-if="generalError" class="form-error" style="margin-bottom: 16px">{{ generalError }}</p>

            <form @submit.prevent="submit">
                <div class="field">
                    <label>Email</label>
                    <input v-model="form.email" type="email" class="input" required autocomplete="email" />
                    <p v-if="errors.email" class="form-error">{{ errors.email[0] }}</p>
                </div>
                <div class="field">
                    <label>Kata sandi</label>
                    <input v-model="form.password" type="password" class="input" required autocomplete="current-password" />
                    <p v-if="errors.password" class="form-error">{{ errors.password[0] }}</p>
                </div>
                <button type="submit" class="btn btn--block" :disabled="submitting">
                    {{ submitting ? 'Memproses…' : 'Masuk' }}
                </button>
            </form>

            <p class="auth-card__footer muted">
                Belum punya akun? <RouterLink to="/register">Daftar</RouterLink>
            </p>
        </div>
    </div>
</template>

<style scoped>
.auth-page {
    padding: 64px 24px;
    display: flex;
    justify-content: center;
}

.auth-card {
    width: 100%;
    max-width: 380px;
    padding: 32px;
}

.auth-card__title {
    margin: 8px 0 24px;
    font-size: 22px;
    font-weight: 600;
}

.auth-card__footer {
    margin: 20px 0 0;
    font-size: 13px;
    text-align: center;
}
</style>

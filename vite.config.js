import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [
    tailwindcss(),                                    // ให้ Tailwind v4 ทำงานกับ Vite
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],  // ไฟล์ที่ build
      refresh: true,
    }),
  ],
})

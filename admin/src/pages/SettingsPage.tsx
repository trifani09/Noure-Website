import { useEffect, useState, type FormEvent } from 'react'
import { ApiError } from '../api/client'
import { PageHeader } from '../components/PageHeader'
import { getSettings, saveSettings } from '../operations/operationsService'
import type { StoreSettings } from '../operations/types'

const blank: StoreSettings = {
  store_name: 'Noure',
  announcement_text: 'Dapatkan harga eksklusif hanya di website',
  announcement_url: '/products',
  announcement_is_active: true,
  support_email: null,
  support_phone: null,
  whatsapp_number: null,
  instagram_url: null,
  default_currency: 'IDR',
  timezone: 'Asia/Jakarta',
  low_stock_threshold: 5,
  order_prefix: 'NOU',
}

export function SettingsPage() {
  const [settings, setSettings] = useState(blank)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [message, setMessage] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    getSettings()
      .then((response) => setSettings(response.data))
      .catch((caught) => setError(caught instanceof ApiError ? caught.message : 'Settings could not be loaded.'))
      .finally(() => setLoading(false))
  }, [])

  async function submit(event: FormEvent) {
    event.preventDefault()
    setSaving(true)
    setError(null)
    setMessage(null)
    try {
      const response = await saveSettings(settings)
      setSettings(response.data)
      setMessage('Settings saved.')
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : 'Settings could not be saved.')
    } finally {
      setSaving(false)
    }
  }

  const field = (key: keyof StoreSettings, label: string, type = 'text') => (
    <label className="space-y-1 text-sm">
      <span className="font-medium">{label}</span>
      <input
        type={type}
        className="w-full rounded-lg border px-3 py-2"
        value={settings[key] === null || typeof settings[key] === 'boolean' ? '' : settings[key]}
        onChange={(event) => setSettings({ ...settings, [key]: type === 'number' ? Number(event.target.value) : event.target.value || null })}
      />
    </label>
  )

  return (
    <div className="space-y-6">
      <PageHeader title="Settings" description="Manage store identity, contacts, and operational defaults." />
      <form onSubmit={submit} className="space-y-8 rounded-xl border bg-white p-6 shadow-sm">
        <section>
          <h2 className="font-semibold">Storefront announcement</h2>
          <p className="mt-1 text-sm text-stone-500">Shown above the storefront header when enabled.</p>
          <div className="mt-4 grid gap-5 md:grid-cols-2">
            {field('announcement_text', 'Announcement text')}
            {field('announcement_url', 'Destination path')}
            <label className="flex items-center gap-3 text-sm">
              <input
                type="checkbox"
                checked={settings.announcement_is_active}
                onChange={(event) => setSettings({ ...settings, announcement_is_active: event.target.checked })}
              />
              <span className="font-medium">Show announcement bar</span>
            </label>
          </div>
        </section>
        <section className="border-t pt-6">
          <h2 className="font-semibold">Store details</h2>
          <div className="mt-4 grid gap-5 md:grid-cols-2">
            {field('store_name', 'Store name')}
            {field('support_email', 'Support email', 'email')}
            {field('support_phone', 'Support phone')}
            {field('whatsapp_number', 'WhatsApp number')}
            {field('instagram_url', 'Instagram URL', 'url')}
            {field('default_currency', 'Default currency')}
            {field('timezone', 'Timezone')}
            {field('low_stock_threshold', 'Low stock threshold', 'number')}
            {field('order_prefix', 'Order prefix')}
          </div>
        </section>
        {error && <p className="text-sm text-red-700">{error}</p>}
        {message && <p className="text-sm text-emerald-700">{message}</p>}
        <button disabled={loading || saving} className="rounded-lg bg-stone-900 px-5 py-2.5 text-sm font-medium text-white disabled:opacity-50">
          {saving ? 'Saving…' : 'Save settings'}
        </button>
      </form>
    </div>
  )
}

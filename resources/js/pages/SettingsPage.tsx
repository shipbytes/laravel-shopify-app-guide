import { useEffect, useState } from 'react';
import { ApiError, get, put } from '../api';
import type { Settings } from '../types';

export function SettingsPage({ onBack }: { onBack: () => void }) {
    const [settings, setSettings] = useState<Settings | null>(null);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [saving, setSaving] = useState(false);

    useEffect(() => { get<Settings>('/settings').then(setSettings); }, []);

    async function save() {
        if (!settings) return;
        setSaving(true);
        setErrors({});
        try {
            setSettings(await put<Settings>('/settings', settings));
            shopify.toast.show('Settings saved');
        } catch (e) {
            if (e instanceof ApiError && e.status === 422) setErrors(e.errors);
            else shopify.toast.show('Could not save settings', { isError: true });
        } finally {
            setSaving(false);
        }
    }

    return (
        <s-page heading="Settings">
            <s-button slot="secondary-actions" onClick={onBack}>Back to reviews</s-button>
            <s-button slot="primary-action" variant="primary" loading={saving} disabled={!settings} onClick={save}>Save</s-button>

            <s-section heading="Moderation">
                {settings ? (
                    <s-stack gap="base">
                        <s-switch
                            label="Publish storefront reviews automatically"
                            details="Off: new reviews wait in Pending until you approve them."
                            name="auto_approve"
                            checked={settings.auto_approve}
                            onChange={(e) => setSettings({ ...settings, auto_approve: e.currentTarget.checked })}
                        />
                        <s-text-field
                            label="Storefront heading"
                            name="widget_title"
                            value={settings.widget_title}
                            error={errors.widget_title?.[0]}
                            onInput={(e) => setSettings({ ...settings, widget_title: e.currentTarget.value })}
                        />
                    </s-stack>
                ) : <s-spinner accessibilityLabel="Loading settings" />}
            </s-section>
        </s-page>
    );
}

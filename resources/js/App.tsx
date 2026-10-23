import { useState } from 'react';
import { ReviewsPage } from './pages/ReviewsPage';
import { SettingsPage } from './pages/SettingsPage';

type View = 'reviews' | 'settings';

/**
 * Two screens, no router: the reviews list and the settings form. App Bridge owns the
 * admin chrome around the iframe; the app only renders what goes inside it.
 */
export function App() {
    const [view, setView] = useState<View>('reviews');

    return view === 'reviews'
        ? <ReviewsPage onOpenSettings={() => setView('settings')} />
        : <SettingsPage onBack={() => setView('reviews')} />;
}

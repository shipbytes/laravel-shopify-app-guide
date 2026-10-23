/**
 * The app's JSON API. App Bridge intercepts same-origin fetch() calls and adds the
 * ID token as an Authorization header, so nothing here deals with tokens.
 */
export class ApiError extends Error {
    constructor(
        public status: number,
        message: string,
        public errors: Record<string, string[]> = {},
    ) {
        super(message);
    }
}

export async function api<T>(path: string, init: RequestInit = {}): Promise<T> {
    const response = await fetch(`/api${path}`, {
        ...init,
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...init.headers },
    });

    if (response.status === 204) {
        return undefined as T;
    }

    const body = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new ApiError(response.status, body.message ?? response.statusText, body.errors ?? {});
    }

    return body as T;
}

export const get = <T>(path: string) => api<T>(path);
export const post = <T>(path: string, data: unknown) => api<T>(path, { method: 'POST', body: JSON.stringify(data) });
export const put = <T>(path: string, data: unknown) => api<T>(path, { method: 'PUT', body: JSON.stringify(data) });
export const patch = <T>(path: string, data: unknown) => api<T>(path, { method: 'PATCH', body: JSON.stringify(data) });
export const destroy = (path: string) => api<void>(path, { method: 'DELETE' });

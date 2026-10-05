<x-error-page code="403" title="Not allowed" message="{{ $exception->getMessage() ?: 'Your account does not have access to this page.' }}" />

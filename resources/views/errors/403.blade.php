<x-error-page code="403" :title="__('Not allowed')" :message="$exception->getMessage() ?: __('Your account does not have access to this page.')" />

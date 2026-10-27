@php
    $addProviderHasErrors = old('_provider_form') === 'add' && $errors->any();
    $oldProviderName = $addProviderHasErrors ? (string) old('name', '') : '';
    $oldProviderStatus = $addProviderHasErrors ? (string) old('status', 'active') : 'active';
@endphp

<div
    class="modal fade"
    id="addProviderModal"
    tabindex="-1"
    aria-labelledby="addProviderModalTitle"
    aria-hidden="true"
    data-reopen-on-error="{{ $addProviderHasErrors ? 'true' : 'false' }}"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ai-provider-modal-content">
            <div class="modal-header border-bottom-0">
                <div>
                    <h5 class="modal-title fw-bold" id="addProviderModalTitle">Add New AI Provider</h5>
                    <p class="ai-provider-modal-subtitle mb-0">Register a supported provider using its official API endpoint.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form
                action="{{ route('admin.ai.providers.store') }}"
                method="POST"
                class="ai-provider-form"
                id="addProviderForm"
                data-provider-add-form
            >
                @csrf
                <input type="hidden" name="_provider_form" value="add">

                <div class="modal-body">
                    @if($availableProviderOptions->isEmpty())
                        <div class="alert alert-info ai-provider-form-notice mb-3" role="status">
                            All supported AI providers are already registered. Use the Actions menu to edit an existing provider.
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label" for="addProviderName">Provider Name</label>
                        <select
                            name="name"
                            id="addProviderName"
                            @class(['form-select', 'input-dark', 'is-invalid' => $addProviderHasErrors && $errors->has('name')])
                            data-provider-select
                            required
                            @disabled($availableProviderOptions->isEmpty())
                        >
                            <option value="">Select a provider</option>
                            @foreach($availableProviderOptions as $providerOption)
                                <option
                                    value="{{ $providerOption['key'] }}"
                                    data-provider-option="{{ $providerOption['key'] }}"
                                    data-endpoint="{{ $providerOption['endpoint'] }}"
                                    @selected($oldProviderName === $providerOption['key'] || $oldProviderName === $providerOption['label'])
                                >
                                    {{ $providerOption['label'] }}
                                </option>
                            @endforeach
                        </select>
                        @if($addProviderHasErrors)
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="addProviderEndpoint">API Endpoint</label>
                        <input
                            type="url"
                            name="api_endpoint"
                            id="addProviderEndpoint"
                            value="{{ $addProviderHasErrors ? old('api_endpoint') : '' }}"
                            @class(['form-control', 'input-dark', 'is-invalid' => $addProviderHasErrors && $errors->has('api_endpoint')])
                            placeholder="Select a provider to fill its official endpoint"
                            maxlength="2048"
                            autocomplete="url"
                            required
                            @disabled($availableProviderOptions->isEmpty())
                        >
                        <div class="form-text ai-provider-field-help">The endpoint must use HTTPS and match the selected provider.</div>
                        @if($addProviderHasErrors)
                            @error('api_endpoint')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="addProviderApiKey">API Key</label>
                        <div class="input-group ai-provider-key-group">
                            <input
                                type="password"
                                name="api_key"
                                id="addProviderApiKey"
                                @class(['form-control', 'input-dark', 'is-invalid' => $addProviderHasErrors && $errors->has('api_key')])
                                placeholder="Paste the provider API key"
                                maxlength="8192"
                                autocomplete="new-password"
                                autocapitalize="none"
                                spellcheck="false"
                                required
                                @disabled($availableProviderOptions->isEmpty())
                            >
                            <button
                                class="btn btn-outline-secondary"
                                type="button"
                                data-provider-key-toggle
                                data-target="addProviderApiKey"
                                aria-controls="addProviderApiKey"
                                aria-label="Show API key"
                                @disabled($availableProviderOptions->isEmpty())
                            >
                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="form-text ai-provider-field-help">The key is encrypted before it is stored and is never shown again.</div>
                        @if($addProviderHasErrors)
                            @error('api_key')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>

                    <div class="mb-0">
                        <label class="form-label" for="addProviderStatus">Status</label>
                        <select
                            name="status"
                            id="addProviderStatus"
                            @class(['form-select', 'input-dark', 'is-invalid' => $addProviderHasErrors && $errors->has('status')])
                            required
                            @disabled($availableProviderOptions->isEmpty())
                        >
                            <option value="active" @selected($oldProviderStatus === 'active')>Active</option>
                            <option value="inactive" @selected($oldProviderStatus === 'inactive')>Inactive</option>
                        </select>
                        <div class="form-text ai-provider-field-help">Active providers can be tested and assigned to routing roles.</div>
                        @if($addProviderHasErrors)
                            @error('status')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button
                        type="submit"
                        class="btn btn-primary"
                        data-provider-save-button
                        @disabled($availableProviderOptions->isEmpty())
                    >
                        <i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>
                        <span data-provider-save-label>Save Provider</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

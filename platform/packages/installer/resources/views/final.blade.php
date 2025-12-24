@extends('packages/installer::layouts.master')

@section('pageTitle', trans('packages/installer::installer.final.pageTitle'))

@section('content')
    <div class="d-flex flex-column justify-content-center h-100">
        <div class="text-center mb-4">
            <x-core::icon
                size="lg"
                name="ti ti-circle-check"
                class="d-block mx-auto text-success mb-2"
            />

            <h3 class="fw-bold mb-2">{{ trans('packages/installer::installer.final.pageTitle') }}</h3>

            <p class="text-secondary">{{ trans('packages/installer::installer.final.message') }}</p>
        </div>

        {{-- Installation Details --}}
        <div class="row mt-4">
            <div class="col-md-6 mb-3">
                <x-core::card>
                    <x-core::card.header>
                        <x-core::card.title>
                            <x-core::icon name="ti ti-world" class="me-2" />
                            {{ trans('packages/installer::installer.final.website_details') }}
                        </x-core::card.title>
                    </x-core::card.header>
                    <x-core::card.body>
                        <div class="mb-3">
                            <strong>{{ trans('packages/installer::installer.final.website_name') }}:</strong>
                            <div class="text-muted">{{ $installationDetails['website']['name'] }}</div>
                        </div>
                        <div>
                            <strong>{{ trans('packages/installer::installer.final.website_url') }}:</strong>
                            <div class="text-muted">
                                <a href="{{ $installationDetails['website']['url'] }}" target="_blank" class="text-decoration-none">
                                    {{ $installationDetails['website']['url'] }}
                                    <x-core::icon name="ti ti-external-link" size="sm" />
                                </a>
                            </div>
                        </div>
                    </x-core::card.body>
                </x-core::card>
            </div>

            <div class="col-md-6 mb-3">
                <x-core::card>
                    <x-core::card.header>
                        <x-core::card.title>
                            <x-core::icon name="ti ti-database" class="me-2" />
                            {{ trans('packages/installer::installer.final.database_details') }}
                        </x-core::card.title>
                    </x-core::card.header>
                    <x-core::card.body>
                        <div class="mb-2">
                            <strong>{{ trans('packages/installer::installer.final.database_connection') }}:</strong>
                            <div class="text-muted text-uppercase">{{ $installationDetails['database']['connection'] }}</div>
                        </div>
                        <div class="mb-2">
                            <strong>{{ trans('packages/installer::installer.final.database_host') }}:</strong>
                            <div class="text-muted">{{ $installationDetails['database']['host'] }}:{{ $installationDetails['database']['port'] }}</div>
                        </div>
                        <div class="mb-2">
                            <strong>{{ trans('packages/installer::installer.final.database_name') }}:</strong>
                            <div class="text-muted">{{ $installationDetails['database']['database'] ?: trans('packages/installer::installer.final.not_set') }}</div>
                        </div>
                        <div class="mb-2">
                            <strong>{{ trans('packages/installer::installer.final.database_username') }}:</strong>
                            <div class="text-muted">{{ $installationDetails['database']['username'] ?: trans('packages/installer::installer.final.not_set') }}</div>
                        </div>
                        <div>
                            <strong>{{ trans('packages/installer::installer.final.database_password') }}:</strong>
                            <div class="text-muted">{{ $installationDetails['database']['password'] ?: trans('packages/installer::installer.final.not_set') }}</div>
                        </div>
                    </x-core::card.body>
                </x-core::card>
            </div>
        </div>

        <div class="alert alert-info mt-3">
            <x-core::icon name="ti ti-info-circle" class="me-2" />
            <strong>{{ trans('packages/installer::installer.final.note') }}:</strong>
            {{ trans('packages/installer::installer.final.note_message') }}
        </div>
    </div>
@endsection

@section('footer')
    <x-core::button
        tag="a"
        color="primary"
        :href="route('access.login')"
    >
        {{ trans('packages/installer::installer.final.exit') }}
    </x-core::button>
@endsection

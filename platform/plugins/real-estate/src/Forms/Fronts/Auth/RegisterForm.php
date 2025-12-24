<?php

namespace Botble\RealEstate\Forms\Fronts\Auth;

use Botble\Base\Facades\Html;
use Botble\Base\Forms\FieldOptions\CheckboxFieldOption;
use Botble\Base\Forms\Fields\EmailField;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\Base\Forms\Fields\OnOffCheckboxField;
use Botble\Base\Forms\Fields\PasswordField;
use Botble\Base\Forms\Fields\PhoneNumberField;
use Botble\Base\Forms\Fields\TextField;
use Botble\RealEstate\Forms\Fronts\Auth\FieldOptions\EmailFieldOption;
use Botble\RealEstate\Forms\Fronts\Auth\FieldOptions\TextFieldOption;
use Botble\RealEstate\Http\Requests\Fronts\Auth\RegisterRequest;
use Botble\RealEstate\Models\Account;

class RegisterForm extends AuthForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->setUrl(route('public.account.register.post'))
            ->setValidatorClass(RegisterRequest::class)
            ->icon('ti ti-user-plus')
            ->heading(trans('plugins/real-estate::account.register_heading'))
            ->description(trans('plugins/real-estate::account.register_description'))
            ->when(
                theme_option('register_background'),
                fn (AuthForm $form, string $background) => $form->banner($background)
            )
            ->add(
                'first_name',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/real-estate::account.first_name'))
                    ->placeholder(trans('plugins/real-estate::account.first_name'))
                    ->icon('ti ti-user')
                    ->required()
            )
            ->add(
                'last_name',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/real-estate::account.last_name'))
                    ->placeholder(trans('plugins/real-estate::account.last_name'))
                    ->icon('ti ti-user')
                    ->required()
            )
            ->when(! setting('real_estate_hide_username_in_registration_page', false), function (): void {
                $this
                    ->add(
                        'username',
                        TextField::class,
                        TextFieldOption::make()
                            ->label(trans('plugins/real-estate::account.username'))
                            ->placeholder(trans('plugins/real-estate::account.username'))
                            ->icon('ti ti-user')
                            ->required()
                    );
            })
            ->add(
                'email',
                EmailField::class,
                EmailFieldOption::make()
                    ->label(trans('plugins/real-estate::account.email'))
                    ->placeholder(trans('plugins/real-estate::account.email_placeholder'))
                    ->icon('ti ti-mail')
                    ->required()
            )
            ->add(
                'phone',
                PhoneNumberField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/real-estate::account.phone_optional'))
                    ->when((bool) setting('real_estate_make_account_phone_number_required', false), function (TextFieldOption $fieldOption): void {
                        $fieldOption
                            ->required()
                            ->label(trans('plugins/real-estate::account.phone'));
                    })
                    ->placeholder(trans('plugins/real-estate::account.phone_placeholder'))
                    ->icon('ti ti-phone')
                    ->addAttribute('autocomplete', 'tel')
                    ->toArray()
            )
            ->add(
                'password',
                PasswordField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/real-estate::account.form.password'))
                    ->placeholder(trans('plugins/real-estate::account.form.password'))
                    ->icon('ti ti-lock')
                    ->required()
            )
            ->add(
                'password_confirmation',
                PasswordField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/real-estate::account.form.password_confirmation'))
                    ->placeholder(trans('plugins/real-estate::account.form.password_confirmation'))
                    ->icon('ti ti-lock')
                    ->required()
            )
            ->add(
                'agree_terms_and_policy',
                OnOffCheckboxField::class,
                CheckboxFieldOption::make()
                    ->when(
                        $privacyPolicyUrl = theme_option('term_and_privacy_policy_url'),
                        function (CheckboxFieldOption $fieldOption, string $url): void {
                            $fieldOption->label(trans('plugins/real-estate::account.agree_to_link', ['link' => Html::link($url, trans('plugins/real-estate::account.terms_privacy_policy'), attributes: ['class' => 'text-decoration-underline', 'target' => '_blank'])]));
                        }
                    )
                    ->when(! $privacyPolicyUrl, function (CheckboxFieldOption $fieldOption): void {
                        $fieldOption->label(trans('plugins/real-estate::account.agree_to_terms'));
                    })
            )
            ->submitButton(trans('plugins/real-estate::account.register'), 'ti ti-arrow-narrow-right')
            ->add('login', HtmlField::class, [
                'html' => sprintf(
                    '<div class="mt-3 text-center">%s <a href="%s" class="text-decoration-underline">%s</a></div>',
                    trans('plugins/real-estate::account.already_have_account'),
                    route('public.account.login'),
                    trans('plugins/real-estate::account.login')
                ),
            ])
            ->add('filters', HtmlField::class, [
                'html' => apply_filters(BASE_FILTER_AFTER_LOGIN_OR_REGISTER_FORM, null, Account::class),
            ]);
    }
}

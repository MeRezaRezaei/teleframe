<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountPasskeyRegistrationOptionsPasskeyRegistrationOptions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthPasskeyLoginOptionsPasskeyLoginOptions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpPassportConfigPassportConfig;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpTermsOfServiceTermsOfService;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageMediaInvoice;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaInvoice;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPasskeyResponseInputPasskeyResponseLogin;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPasskeyResponseInputPasskeyResponseRegister;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPaymentCredentialsInputPaymentCredentials;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPaymentCredentialsInputPaymentCredentialsApplePay;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPaymentCredentialsInputPaymentCredentialsGooglePay;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentFormPaymentForm;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallPhoneCall;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSendMessageActionSendMessageEmojiInteraction;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsGraphStatsGraph;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotWebhookJSON;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotWebhookJSONQuery;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateGroupCallConnection;

/** Anchor model for TL type DataJSON (spec §4.1). */
final class TlDataJSON extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_data_j_s_o_n_data_j_s_o_n';

    protected $guarded = [];

    public function clientData(): HasMany
    {
        return $this->hasMany(TlInputPasskeyResponseInputPasskeyResponseRegister::class, 'client_data');
    }
    public function clientDataInputPasskeyResponseLogin(): HasMany
    {
        return $this->hasMany(TlInputPasskeyResponseInputPasskeyResponseLogin::class, 'client_data');
    }
    public function countriesLangs(): HasMany
    {
        return $this->hasMany(TlHelpPassportConfigPassportConfig::class, 'countries_langs');
    }
    public function customParameters(): HasMany
    {
        return $this->hasMany(TlPhoneCallPhoneCall::class, 'custom_parameters');
    }
    public function data(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateBotWebhookJSON::class, 'data');
    }
    public function dataInputPaymentCredentials(): HasMany
    {
        return $this->hasMany(TlInputPaymentCredentialsInputPaymentCredentials::class, 'data');
    }
    public function dataUpdateBotWebhookJSONQuery(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateBotWebhookJSONQuery::class, 'data');
    }
    public function id(): HasMany
    {
        return $this->hasMany(TlHelpTermsOfServiceTermsOfService::class, 'tl_id');
    }
    public function interaction(): HasMany
    {
        return $this->hasMany(TlSendMessageActionSendMessageEmojiInteraction::class, 'interaction');
    }
    public function json(): HasMany
    {
        return $this->hasMany(TlStatsGraphStatsGraph::class, 'json');
    }
    public function nativeParams(): HasMany
    {
        return $this->hasMany(TlPaymentsPaymentFormPaymentForm::class, 'native_params');
    }
    public function options(): HasMany
    {
        return $this->hasMany(TlAccountPasskeyRegistrationOptionsPasskeyRegistrationOptions::class, 'options');
    }
    public function optionsAuthPasskeyLoginOptions(): HasMany
    {
        return $this->hasMany(TlAuthPasskeyLoginOptionsPasskeyLoginOptions::class, 'options');
    }
    public function params(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateGroupCallConnection::class, 'params');
    }
    public function paymentData(): HasMany
    {
        return $this->hasMany(TlInputPaymentCredentialsInputPaymentCredentialsApplePay::class, 'payment_data');
    }
    public function paymentToken(): HasMany
    {
        return $this->hasMany(TlInputPaymentCredentialsInputPaymentCredentialsGooglePay::class, 'payment_token');
    }
    public function providerData(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaInvoice::class, 'provider_data');
    }
    public function providerDataInputBotInlineMessageMediaInvoice(): HasMany
    {
        return $this->hasMany(TlInputBotInlineMessageInputBotInlineMessageMediaInvoice::class, 'provider_data');
    }
}

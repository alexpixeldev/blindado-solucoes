<?php
/**
 * Configuração da integração com o Moni Software.
 *
 * Serviço principal (API REST): porta 8082.
 * Moni Integração (fotos/credencial facial): porta 1025, autenticação Basic.
 */
return [
    'base_url' => 'http://54.232.189.113:8082',
    'integration_url' => 'http://54.232.189.113:1025',
    'username' => 'alex',
    'password' => '200502',
    'integration_username' => 'personalizada',
    'integration_password' => 'personalizada',
];

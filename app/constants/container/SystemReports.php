<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class SystemReports extends AConstant {
    public const MY_HOME_OFFICE_REQUESTS = 'myHomeOfficeRequests';
    public const INVOICES = 'invoices';

    public static function toString($key): ?string {
        return match($key) {
            self::MY_HOME_OFFICE_REQUESTS => 'My Home Office requests',
            self::INVOICES => 'Invoices'
        };
    }

    /**
     * Returns metadata for creation
     * 
     * @param string $key Report key
     */
    public static function getMetadataForCreation(string $key): array {
        return match($key) {
            self::MY_HOME_OFFICE_REQUESTS => [
                'title' => self::toString($key),
                'description' => 'My Home Office requests',
                'name' => $key,
                'isEnabled' => 1,
                'definition' => self::getDefinitionBase64($key)
            ],
            self::INVOICES => [
                'title' => self::toString($key),
                'description' => 'Invoices',
                'name' => $key,
                'isEnabled' => 1,
                'definition' => self::getDefinitionBase64($key)
            ]
        };
    }

    /**
     * Returns report definition in base 64
     * 
     * @param string $key Report key
     */
    private static function getDefinitionBase64(string $key): string {
        $result = match($key) {
            self::MY_HOME_OFFICE_REQUESTS => 'ew0KICAgICJ0YWJsZSI6ICJwcm9jZXNzX2luc3RhbmNlcyIsDQogICAgInByaW1hcnlLZXkiOiAiaW5zdGFuY2VJZCIsDQogICAgImNvbHVtbnMiOiBbDQogICAgICAgIHsNCiAgICAgICAgICAgICJuYW1lIjogInVzZXJJZCIsDQogICAgICAgICAgICAidGl0bGUiOiAiQXV0aG9yIiwNCiAgICAgICAgICAgICJ0eXBlIjogInVzZXIiDQogICAgICAgIH0sDQogICAgICAgIHsNCiAgICAgICAgICAgICJuYW1lIjogInN0YXR1cyIsDQogICAgICAgICAgICAidGl0bGUiOiAiU3RhdHVzIiwNCiAgICAgICAgICAgICJ0eXBlIjogImVudW0iLA0KICAgICAgICAgICAgImVudW1DbGFzcyI6ICJcXEFwcFxcQ29uc3RhbnRzXFxDb250YWluZXJcXFByb2Nlc3NJbnN0YW5jZVN0YXR1cyINCiAgICAgICAgfSwNCiAgICAgICAgew0KICAgICAgICAgICAgIm5hbWUiOiAiY3VycmVudE9mZmljZXJJZCIsDQogICAgICAgICAgICAidGl0bGUiOiAiQ3VycmVudCBvZmZpY2VyIiwNCiAgICAgICAgICAgICJ0eXBlIjogInVzZXJHcm91cCIsDQogICAgICAgICAgICAidHlwZUNoZWNrQ29sdW1uRW51bSI6IHsNCiAgICAgICAgICAgICAgICAibmFtZSI6ICJjdXJyZW50T2ZmaWNlclR5cGUiLA0KICAgICAgICAgICAgICAgICJ1c2VyS2V5IjogMSwNCiAgICAgICAgICAgICAgICAiZ3JvdXBLZXkiOiAyDQogICAgICAgICAgICB9DQogICAgICAgIH0sDQogICAgICAgIHsNCiAgICAgICAgICAgICJuYW1lIjogImRhdGVDcmVhdGVkIiwNCiAgICAgICAgICAgICJ0aXRsZSI6ICJEYXRlIGNyZWF0ZWQiLA0KICAgICAgICAgICAgInR5cGUiOiAiZGF0ZXRpbWUiDQogICAgICAgIH0sDQogICAgICAgIHsNCiAgICAgICAgICAgICJuYW1lIjogImRhdGVGcm9tIiwNCiAgICAgICAgICAgICJ0aXRsZSI6ICJEYXRlIGZyb20iLA0KICAgICAgICAgICAgInR5cGUiOiAicHJvY2Vzc0luc3RhbmNlRGF0YV9kYXRldGltZSIsDQogICAgICAgICAgICAianNvblBhdGgiOiAiZm9ybXMuMC5kYXRhLmRhdGVGcm9tIg0KICAgICAgICB9LA0KICAgICAgICB7DQogICAgICAgICAgICAibmFtZSI6ICJkYXRlVG8iLA0KICAgICAgICAgICAgInRpdGxlIjogIkRhdGUgdG8iLA0KICAgICAgICAgICAgInR5cGUiOiAicHJvY2Vzc0luc3RhbmNlRGF0YV9kYXRldGltZSIsDQogICAgICAgICAgICAianNvblBhdGgiOiAiZm9ybXMuMC5kYXRhLmRhdGVUbyINCiAgICAgICAgfQ0KICAgIF0sDQogICAgImZpbHRlciI6ICJzdGF0dXMgPSAyIiwNCiAgICAicHJvY2Vzc0ZpbHRlciI6ICJzeXNfaG9tZU9mZmljZSIsDQogICAgIm9yZGVyIjogWw0KICAgICAgICB7DQogICAgICAgICAgICAibmFtZSI6ICJkYXRlTW9kaWZpZWQiLA0KICAgICAgICAgICAgIm9yZGVyIjogIkFTQyINCiAgICAgICAgfQ0KICAgIF0sDQogICAgImdyaWRDb25maWciOiB7DQogICAgICAgICJwYWdpbmF0aW9uIjogZmFsc2UNCiAgICB9DQp9',
            self::INVOICES => 'ew0KICAgICJ0YWJsZSI6ICJwcm9jZXNzX2luc3RhbmNlcyIsDQogICAgInByaW1hcnlLZXkiOiAiaW5zdGFuY2VJZCIsDQogICAgImNvbHVtbnMiOiBbDQogICAgICAgIHsNCiAgICAgICAgICAgICJuYW1lIjogInVzZXJJZCIsDQogICAgICAgICAgICAidGl0bGUiOiAiQXV0aG9yIiwNCiAgICAgICAgICAgICJ0eXBlIjogInVzZXIiDQogICAgICAgIH0sDQogICAgICAgIHsNCiAgICAgICAgICAgICJuYW1lIjogInN0YXR1cyIsDQogICAgICAgICAgICAidGl0bGUiOiAiU3RhdHVzIiwNCiAgICAgICAgICAgICJ0eXBlIjogImVudW0iLA0KICAgICAgICAgICAgImVudW1DbGFzcyI6ICJcXEFwcFxcQ29uc3RhbnRzXFxDb250YWluZXJcXFByb2Nlc3NJbnN0YW5jZVN0YXR1cyINCiAgICAgICAgfSwNCiAgICAgICAgew0KICAgICAgICAgICAgIm5hbWUiOiAiY3VycmVudE9mZmljZXJJZCIsDQogICAgICAgICAgICAidGl0bGUiOiAiQ3VycmVudCBvZmZpY2VyIiwNCiAgICAgICAgICAgICJ0eXBlIjogInVzZXJHcm91cCIsDQogICAgICAgICAgICAidHlwZUNoZWNrQ29sdW1uRW51bSI6IHsNCiAgICAgICAgICAgICAgICAibmFtZSI6ICJjdXJyZW50T2ZmaWNlclR5cGUiLA0KICAgICAgICAgICAgICAgICJ1c2VyS2V5IjogMSwNCiAgICAgICAgICAgICAgICAiZ3JvdXBLZXkiOiAyDQogICAgICAgICAgICB9DQogICAgICAgIH0sDQogICAgICAgIHsNCiAgICAgICAgICAgICJuYW1lIjogImRhdGVDcmVhdGVkIiwNCiAgICAgICAgICAgICJ0aXRsZSI6ICJEYXRlIGNyZWF0ZWQiLA0KICAgICAgICAgICAgInR5cGUiOiAiZGF0ZXRpbWUiDQogICAgICAgIH0sDQogICAgICAgIHsNCiAgICAgICAgICAgICJuYW1lIjogInN1bSIsDQogICAgICAgICAgICAidGl0bGUiOiAiU3VtIiwNCiAgICAgICAgICAgICJ0eXBlIjogInByb2Nlc3NJbnN0YW5jZURhdGFfdGV4dENvbWJpbmF0aW9uIiwNCiAgICAgICAgICAgICJjb21iaW5hdGlvblBhcnRzIjogWw0KICAgICAgICAgICAgICAgIHsNCiAgICAgICAgICAgICAgICAgICAgInR5cGUiOiAicHJvY2Vzc0luc3RhbmNlRGF0YV90ZXh0IiwNCiAgICAgICAgICAgICAgICAgICAgImpzb25QYXRoIjogImZvcm1zLjAuZGF0YS5zdW0iDQogICAgICAgICAgICAgICAgfSwNCiAgICAgICAgICAgICAgICB7DQogICAgICAgICAgICAgICAgICAgICJ0eXBlIjogInRleHQiLA0KICAgICAgICAgICAgICAgICAgICAidmFsdWUiOiAiICINCiAgICAgICAgICAgICAgICB9LA0KICAgICAgICAgICAgICAgIHsNCiAgICAgICAgICAgICAgICAgICAgInR5cGUiOiAicHJvY2Vzc0luc3RhbmNlRGF0YV9lbnVtIiwNCiAgICAgICAgICAgICAgICAgICAgImpzb25QYXRoIjogImZvcm1zLjAuZGF0YS5zdW1DdXJyZW5jeSIsDQogICAgICAgICAgICAgICAgICAgICJlbnVtQ2xhc3MiOiAiXFxBcHBcXENvbnN0YW50c1xcQ29udGFpbmVyXFxJbnZvaWNlQ3VycmVuY2llcyINCiAgICAgICAgICAgICAgICB9DQogICAgICAgICAgICBdDQogICAgICAgIH0NCiAgICBdLA0KICAgICJwcm9jZXNzRmlsdGVyIjogInN5c19pbnZvaWNlIiwNCiAgICAib3JkZXIiOiBbDQogICAgICAgIHsNCiAgICAgICAgICAgICJuYW1lIjogImRhdGVNb2RpZmllZCIsDQogICAgICAgICAgICAib3JkZXIiOiAiQVNDIg0KICAgICAgICB9DQogICAgXQ0KfQ=='
        };

        return base64_encode($result);
    }
}
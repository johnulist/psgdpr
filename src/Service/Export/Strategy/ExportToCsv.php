<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\Psgdpr\Service\Export\Strategy;

use PrestaShop\Module\Psgdpr\Service\Export\ExportContext;
use PrestaShop\Module\Psgdpr\Service\Export\ExportInterface;
use PrestaShop\Module\Psgdpr\Service\LoggerService;

class ExportToCsv extends ExportContext implements ExportInterface
{
    const TYPE = 'csv';

    /**
     * Generate CSV file from customer data
     *
     * @return string
     */
    public function getData(array $customerData): string
    {
        $buffer = fopen('php://output', 'w');
        ob_start();

        foreach ($customerData as $key => $value) {
            if ($key === 'modules') {
                foreach ($value as $thirdPartyValue) {
                    $this->insertDataInCsv($buffer, $thirdPartyValue);
                }

                continue;
            }

            $this->insertDataInCsv($buffer, $value);
        }

        $csvFile = ob_get_clean();
        fclose($buffer);

        if (empty($csvFile)) {
            return '';
        }

        $customerFullName = $customerData['personalinformations']['data'][0]['firstname'] . ' ' . $customerData['personalinformations']['data'][0]['lastname'];

        $this->loggerService->createLog($customerData['personalinformations']['data'][0]['id'], LoggerService::REQUEST_TYPE_EXPORT_CSV, 0, 0, $customerFullName);

        return $csvFile;
    }

    /**
     * Insert data in CSV file
     *
     * @param mixed $buffer
     * @param mixed $value
     *
     * @return void
     */
    private function insertDataInCsv($buffer, $value)
    {
        fputcsv($buffer, [strtoupper($value['name'])]);
        fputcsv($buffer, $value['headers']);

        foreach ($value['data'] as $data) {
            fputcsv($buffer, $data);
        }

        fputcsv($buffer, []);
    }

    public function supports(string $type): bool
    {
        return $type === self::TYPE;
    }
}
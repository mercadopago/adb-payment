import { test } from '@playwright/test';
import { execSync } from 'child_process';
import { fillStepsToCheckout } from "../flows/fill_steps_to_checkout";

async function setCredentials(use, siteIdParams, page) {
    if (process.env.SELF_CONFIG !== 'true') {
        await use(page);
        return;
    }

    const public_key = process.env[`${siteIdParams.siteId}_PUBLIC_KEY`];
    const access_token = process.env[`${siteIdParams.siteId}_ACCESS_TOKEN`];
    const prefix = process.env.USING_DOCKER === 'true' ? 'docker exec magento_php ' : `${__dirname}/../../`;

    execSync(
        `${prefix}vendor/bin/n98-magerun2 config:store:set --encrypt payment/mercadopago_adbpayment/client_secret_production ${access_token}`,
        { stdio: 'ignore' }
    );
    execSync(
        `${prefix}bin/magento config:set payment/mercadopago_adbpayment/client_id_production ${public_key}`,
        { stdio: 'ignore' }
    );

    await use(page);
}

exports.test = test.extend({
    siteIdParams: [{}, { option: true }],
    page: async ({ page, siteIdParams }, use) => {
        await fillStepsToCheckout(page, siteIdParams.url, siteIdParams.user);
        await setCredentials(use, siteIdParams, page);
    },
});

exports.testWithoutFillSteps = test.extend({
    siteIdParams: [{}, { option: true }],
    page: async ({ page, siteIdParams }, use) => {
        await setCredentials(use, siteIdParams, page);
    },
});

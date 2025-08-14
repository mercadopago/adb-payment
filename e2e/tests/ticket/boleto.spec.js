import { test } from "../test";
import { approvedTest, rejectedTestEmptyDocument } from "./general_tests";
import { setConfig } from "../../helpers";

test.beforeEach(({ siteIdParams }) => {
    test.skip(siteIdParams.siteId !== 'MLB');
});

test.beforeAll(async () => {
    await setConfig('customer/address/street_lines', '4');
    await setConfig('payment/mercadopago_adbpayment_payment_methods_off/specificcountry', 'BR');
    await setConfig('payment/mercadopago_adbpayment_payment_methods_off/active', '1');
});

test('test successful invoice payment boleto, payment must be approved and success page must be shown', async ({ page, siteIdParams }) => {
    await approvedTest(page, siteIdParams.user.document, 'bolbradesco');
});

test('test fail invoice payment boleto with empty document fields', async ({ page }) => {
    await rejectedTestEmptyDocument(page, 'bolbradesco');
});

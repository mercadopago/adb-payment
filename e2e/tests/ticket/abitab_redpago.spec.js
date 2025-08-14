import { setConfig } from "../../helpers";
import { test, testWithoutFillSteps } from "../test";
import { approvedTest, rejectedTestInvalidAmount, rejectedTestEmptyDocument } from "./general_tests";

test.beforeEach(({ siteIdParams }) => {
    test.skip(siteIdParams.siteId !== 'MLU');
});

test.beforeAll(async () => {
    await setConfig('carriers/freeshipping/active', '1');
    await setConfig('carriers/flatrate/active', '0');
});

const methods = [
    'abitab',
    'redpagos'
];

methods.forEach((method) => {
    test(`test successful ${method} payment, payment must be approved and success page must be show`, async ({ page, siteIdParams }) => {
        await approvedTest(page, siteIdParams.user.document, method);
    });
});

methods.forEach((method) => {
    test(`test fail ${method} payment with empty document fields`, async ({ page }) => {
        await rejectedTestEmptyDocument(page, method);
    });
});

methods.forEach((method) => {
    testWithoutFillSteps(`test fail ${method} payment with invalid amount`, async ({ page, siteIdParams }) => {
        await rejectedTestInvalidAmount(page, siteIdParams, method, 0.01);
    });
});

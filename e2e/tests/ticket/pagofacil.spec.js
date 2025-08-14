import { test, testWithoutFillSteps } from "../test";
import { approvedTest, rejectedTestInvalidAmount, rejectedTestEmptyDocument } from "./general_tests";

test.beforeEach(({ siteIdParams }) => {
    test.skip(siteIdParams.siteId !== 'MLA');
});

test('test successful pago facil payment, payment must be approved and success page must be shown', async ({page, siteIdParams}) => {
    await approvedTest(page, siteIdParams.user.document, 'pagofacil');
});

test('test successful rapipago payment, payment must be approved and success page must be shown', async ({page, siteIdParams}) => {
    await approvedTest(page, siteIdParams.user.document, 'rapipago');
});

test('test fail pago facil payment with empty document fields', async ({page}) => {
    await rejectedTestEmptyDocument(page, 'pagofacil');
});

testWithoutFillSteps('test fail rapipago payment with invalid amount', async ({page, siteIdParams}) => {
    await rejectedTestInvalidAmount(page, siteIdParams, 'rapipago', 15.00);
});

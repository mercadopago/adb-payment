import { test } from "./../../test";
import { turnOnBinary } from "../../../helpers";
import { rejectedTest } from "./general_tests";

test.beforeAll(async () => {
    await turnOnBinary();
});

test('test credit pending payment with one card, payment must be rejected and error page must be shown', async ({ page, siteIdParams }) => {
    await rejectedTest(page, siteIdParams, siteIdParams.credit_cards.visa, "CONT");
});

test('test debit pending payment with one card, payment must be rejected and error page must be shown', async ({ page, siteIdParams }) => {
    await rejectedTest(page, siteIdParams, siteIdParams.debit_card, "CONT");
});

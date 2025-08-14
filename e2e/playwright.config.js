// @ts-check
// eslint-disable-next-line import/no-extraneous-dependencies
const { defineConfig, devices } = require("@playwright/test");

// eslint-disable-next-line import/no-extraneous-dependencies
require("dotenv").config();

const siteIdParams = {
    url: process.env.STORE_URL,
    admin: {
        url: process.env.STORE_ADMIN_URL,
        user: process.env.STORE_ADMIN_USER,
        password: process.env.STORE_ADMIN_PASSWORD,
    },
    user: {
        firstName: "John",
        lastName: "Doe",
        phone: "11997979797",
        address: {
            street: "Test St",
            countryId: "BR",
            regionId: "509",
            city: "São Paulo",
            zip: "04533000",
            number: "123",
            complement: "Apt 456",
            neighborhood: "Test Neighborhood",
        }
    },
    credit_cards: {
        master: {
            month: "11",
            year: "30",
            code: "123",
            number: process.env.CC_MASTER,
        },
        visa: {
            month: "11",
            year: "30",
            code: "123",
            number: process.env.CC_VISA,
        },
    },
    debit_card: {
        month: "11",
        year: "30",
        code: "123",
        number: process.env.DC_VISA,
    }
};

/**
 * @see https://playwright.dev/docs/test-configuration
 */
module.exports = defineConfig({
    timeout: 900000,
    testDir: "./tests",
    /* Run tests in files in parallel */
    fullyParallel: true,
    /* Fail the build on CI if you accidentally left test.only in the source code. */
    forbidOnly: !!process.env.CI,
    /* Retry on CI only */
    retries: 3,
    /* Opt out of parallel tests on CI. */
    workers: process.env.CI ? 1 : undefined,
    /* Reporter to use. See https://playwright.dev/docs/test-reporters */
    reporter: "html",
    /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
    use: {
        /* Base URL to use in actions like `await page.goto('/')`. */
        // baseURL: 'http://127.0.0.1:3000',

        /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
        trace: "on-first-retry"
    },

    /* Configure projects for major browsers */
    projects: [
        {
            name: "MLB",
            use: {
                ...devices["Desktop Chrome"],
                siteIdParams: {
                    ...siteIdParams,
                    siteId: "MLB",
                    user: {
                        ...siteIdParams.user,
                        email: process.env.MLB_USER_EMAIL,
                        password: process.env.MLB_USER_PASSWORD,
                        document: {
                            type: 'CPF',
                            number: '12345678909'
                        }
                    }
                }
            }
        },
        {
            name: "MLA",
            use: {
                ...devices["Desktop Chrome"],
                siteIdParams: {
                    ...siteIdParams,
                    siteId: "MLA",
                    user: {
                        ...siteIdParams.user,
                        email: process.env.MLA_USER_EMAIL,
                        password: process.env.MLA_USER_PASSWORD,
                        document: {
                            type: 'DNI',
                            number: '12345678'
                        }
                    }
                }
            }
        },
        {
            name: "MLC",
            use: {
                ...devices["Desktop Chrome"],
                siteIdParams: {
                    ...siteIdParams,
                    siteId: "MLC",
                    user: {
                        ...siteIdParams.user,
                        email: process.env.MLC_USER_EMAIL,
                        password: process.env.MLC_USER_PASSWORD,
                        document: {
                            type: 'otro',
                            number: '123456789'
                        }
                    }
                }
            }
        },
        {
            name: "MCO",
            use: {
                ...devices["Desktop Chrome"],
                siteIdParams: {
                    ...siteIdParams,
                    siteId: "MCO",
                    user: {
                        ...siteIdParams.user,
                        email: process.env.MCO_USER_EMAIL,
                        password: process.env.MCO_USER_PASSWORD,
                        document: {
                            type: 'CC',
                            number: '123456789'
                        },
                        address: {
                            ...siteIdParams.user.address,
                            countryId: "CO",
                            regionId: "718",
                        }
                    }
                }
            }
        },
        {
            name: "MPE",
            use: {
                ...devices["Desktop Chrome"],
                siteIdParams: {
                    ...siteIdParams,
                    siteId: "MPE",
                    user: {
                        ...siteIdParams.user,
                        email: process.env.MPE_USER_EMAIL,
                        password: process.env.MPE_USER_PASSWORD,
                        document: {
                            type: 'DNI',
                            number: '123456789'
                        },
                        address: {
                            ...siteIdParams.user.address,
                            countryId: "PE",
                            regionId: "986",
                        }
                    },
                }
            }
        },
        {
            name: "MLU",
            use: {
                ...devices["Desktop Chrome"],
                siteIdParams: {
                    ...siteIdParams,
                    siteId: "MLU",
                    user: {
                        ...siteIdParams.user,
                        email: process.env.MLU_USER_EMAIL,
                        password: process.env.MLU_USER_PASSWORD,
                        document: {
                            type: 'CI',
                            number: '12345678'
                        }
                    }
                }
            }
        },
        {
            name: "MLM",
            use: {
                ...devices["Desktop Chrome"],
                siteIdParams: {
                    ...siteIdParams,
                    siteId: "MLM",
                    user: {
                        ...siteIdParams.user,
                        email: process.env.MLM_USER_EMAIL,
                        password: process.env.MLM_USER_PASSWORD,
                        document: false
                    }
                }
            }
        },
    ]

    /* Run your local dev server before starting the tests */
    // webServer: {
    //   command: 'npm run start',
    //   url: 'http://127.0.0.1:3000',
    //   reuseExistingServer: !process.env.CI,
    // },
});

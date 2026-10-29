import { expect } from "@playwright/test";
import { test } from "../../setup";
import { ProductCreatePage } from "../../pages/admin/catalog/products/ProductCreatePage";
import { ProductListPage } from "../../pages/admin/catalog/products/ProductListPage";
import { SimpleProductCheckout } from "../../pages/shop/checkout/product-types/SimpleProductCheckout";
import { setConfigSwitch } from "../../utils/admin";
import { uniqueStamp } from "../../utils/faker";

const ORDER_API = "**/api/checkout/onepage/orders";
const CHECKOUT_CONFIG_PATH = "admin/configuration/sales/checkout";
const GUEST_CHECKOUT_FIELD =
    "sales[checkout][shopping_cart][allow_guest_checkout]";

test.describe("checkout failure", () => {
    let productName: string;
    let productListPage: ProductListPage;

    test.beforeEach(async ({ adminPage }) => {
        productListPage = new ProductListPage(adminPage);
        productName = `failure-${uniqueStamp()}`;

        await new ProductCreatePage(adminPage).createProduct({
            type: "simple",
            sku: `SKU-${uniqueStamp()}`,
            name: productName,
            shortDescription: "Short desc",
            description: "Full desc",
            price: 120,
            weight: 1,
            inventory: 10,
        });
    });

    test.afterEach(async () => {
        await productListPage.deleteProductsIfPresent([productName]);
    });

    test("should keep the customer on checkout with an error when the order is declined, then accept a retry", async ({
        adminPage,
        shopPage,
    }) => {
        const original = await setConfigSwitch(
            adminPage,
            CHECKOUT_CONFIG_PATH,
            GUEST_CHECKOUT_FIELD,
            true,
        );

        try {
            await shopPage.route(ORDER_API, (route) =>
                route.fulfill({
                    status: 500,
                    contentType: "application/json",
                    body: JSON.stringify({
                        message: "Payment declined by the gateway",
                    }),
                }),
            );

            await expect(
                new SimpleProductCheckout(shopPage).checkout(productName, {
                    address: "guest",
                }),
            ).rejects.toThrow(/checkout failed \(500\)/);

            await expect(shopPage).toHaveURL(/checkout\/onepage(?!\/success)/);
            await expect(
                shopPage.getByText("Payment declined by the gateway").first(),
            ).toBeVisible();

            const placeOrder = shopPage.getByRole("button", {
                name: "Place Order",
            });

            await expect(placeOrder).toBeEnabled();

            await shopPage.unroute(ORDER_API);
            await placeOrder.click();

            await expect(shopPage).toHaveURL(/checkout\/onepage\/success/, {
                timeout: 30 * 1000,
            });
        } finally {
            await setConfigSwitch(
                adminPage,
                CHECKOUT_CONFIG_PATH,
                GUEST_CHECKOUT_FIELD,
                original,
            );
        }
    });
});

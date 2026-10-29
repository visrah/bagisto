import { test, expect } from "../setup";

test.describe("merchant experience @merchant", () => {
    test.skip(
        process.env.SHOP_ADMIN_MODE !== "simple",
        "Run against a merchant preview with SHOP_ADMIN_MODE=simple.",
    );

    test("keeps mobile navigation accessible without horizontal overflow", async ({
        adminPage: page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto("admin/dashboard");
        await expect(
            page.getByRole("heading", { name: "Dashboard", exact: true }),
        ).toBeVisible();

        const toggle = page
            .getByRole("button", { name: "Toggle navigation" })
            .first();
        await toggle.click();
        const drawer = page.getByRole("dialog");
        await expect(drawer).toBeVisible();
        await page.keyboard.press("Tab");
        await expect(
            drawer.getByRole("button", { name: "Close", exact: true }),
        ).toBeFocused();
        await page.keyboard.press("Shift+Tab");
        await expect(
            drawer.getByRole("link", { name: "Help & Resources" }),
        ).toBeFocused();
        await page.keyboard.press("Escape");
        await expect(drawer).not.toBeVisible();
        await expect(toggle).toBeFocused();
        expect(
            await page.evaluate(
                () => document.documentElement.scrollWidth <= window.innerWidth,
            ),
        ).toBe(true);
    });

    test("creates and edits a product with a photo while retaining the photo and stock", async ({
        adminPage: page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto("admin/catalog/products/new");
        const name = `Merchant tea ${Date.now()}`;
        let productId: string | undefined;
        try {
            await page.locator("#name").fill(name);
            await page
                .locator("#description")
                .fill("Tea grown for our local shop.");
            await page.locator("#price").fill("125.50");
            await page.locator("#quantity").fill("8");
            await page.locator("#product-images").setInputFiles({
                name: "tea.png",
                mimeType: "image/png",
                buffer: Buffer.from(
                    "iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAIAAABLbSncAAAAFElEQVR4nGOUiwpgwAaYsIoOWgkAoYYA2GcSUbUAAAAASUVORK5CYII=",
                    "base64",
                ),
            });
            await page
                .getByRole("button", { name: "Save product", exact: true })
                .click();
            await expect(page).toHaveURL(/catalog\/products\/edit\/\d+/);
            productId = page.url().split("/").pop();
            await expect(page.locator("#name")).toHaveValue(name);
            await page.waitForLoadState("networkidle");
            const retainedImages = page.locator(
                'input[type="hidden"][name^="images[files]"]',
            );
            await expect(retainedImages).toHaveCount(1);
            await expect(
                page.locator('input[name^="inventories["]').first(),
            ).toHaveValue("8");
            await page.locator("#price").fill("135.50");
            await page
                .getByRole("button", { name: "Save Product", exact: true })
                .click();
            await expect(page).toHaveURL(/catalog\/products$/);
            await page.goto(`admin/catalog/products/edit/${productId}`);
            await expect
                .poll(async () =>
                    Number(await page.locator("#price").inputValue()),
                )
                .toBe(135.5);
            await expect(retainedImages).toHaveCount(1);
            await expect(
                page.locator('input[name^="inventories["]').first(),
            ).toHaveValue("8");
        } finally {
            if (productId) {
                const token = await page
                    .locator('input[name="_token"]')
                    .first()
                    .inputValue();
                const response = await page.request.delete(
                    `admin/catalog/products/edit/${productId}`,
                    { data: { _token: token } },
                );
                expect(response.ok()).toBe(true);
            }
        }
    });

    test("shows payment and delivery independently on desktop", async ({
        adminPage: page,
    }) => {
        await page.setViewportSize({ width: 1440, height: 1000 });
        await page.goto("admin/sales/orders");
        await expect(
            page.getByRole("columnheader", { name: "Payment", exact: true }),
        ).toBeVisible();
        await expect(
            page.getByRole("columnheader", { name: "Delivery", exact: true }),
        ).toBeVisible();
        expect(
            await page.evaluate(
                () => document.documentElement.scrollWidth <= window.innerWidth,
            ),
        ).toBe(true);
    });
});

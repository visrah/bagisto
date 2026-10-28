<?php

namespace Webkul\ShopSetup\Themes;

/**
 * The home page hero each store theme starts with.
 *
 * A hero is markup and a stylesheet that draw only with the theme's own variables (surface, ink, accent, tint, heading
 * and body fonts), so it changes with the theme and needs no image. Every theme has its own composition, not a recoloured
 * copy of one, and the owner can replace it with their own picture in the Appearance editor.
 */
class ThemeHeroes
{
    /**
     * Whether a theme has a hero of its own.
     */
    public function has(string $theme): bool
    {
        return isset($this->copy()[$theme]);
    }

    /**
     * The hero of a theme, with the button pointing at the given address.
     *
     * @return array{html: string, css: string}|null
     */
    public function for(string $theme, string $shopUrl): ?array
    {
        $copy = $this->copy()[$theme] ?? null;

        if ($copy === null) {
            return null;
        }

        [$eyebrow, $headline, $line, $button] = $copy;

        $html = <<<HTML
<section class="vh vh-{$theme}">
    <div class="vh-text">
        <p class="vh-eyebrow">{$eyebrow}</p>
        <h1 class="vh-title">{$headline}</h1>
        <p class="vh-line">{$line}</p>
        <a class="vh-button" href="{$shopUrl}">{$button}</a>
    </div>
    <div class="vh-art" aria-hidden="true"><span></span><span></span><span></span></div>
</section>
HTML;

        return ['html' => $html, 'css' => $this->base().$this->styles()[$theme]];
    }

    /**
     * Eyebrow, headline, supporting line and button label of each hero.
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    protected function copy(): array
    {
        return [
            'apparel' => ['New season', 'Dress the way you mean it.', 'Fresh arrivals, easy fits and pieces you will reach for every day.', 'Shop the edit'],
            'electronics' => ['Latest tech', 'Tech that keeps up.', 'Fast delivery, easy returns and secure checkout on every order.', 'Shop devices'],
            'food' => ['Fresh every day', 'Good food, made easy.', 'Picked, packed and on its way to your table.', 'Order now'],
            'home' => ['For every room', 'Make room for what you love.', 'Considered pieces that make a house feel like home.', 'Browse the collection'],
            'beauty' => ['Self-care', 'Glow, your way.', 'Gentle formulas and small rituals for skin that feels like yours.', 'Discover'],
            'kids' => ['Playtime!', 'Play starts here!', 'Toys, games and gifts that keep little hands busy.', 'Let us shop'],
            'sports' => ['Built to move', 'Gear up. Go further.', 'Kit for every run, ride, climb and match.', 'Shop gear'],
            'jewelry' => ['The collection', 'Made to be treasured.', 'Pieces for the moments you want to remember.', 'View the collection'],
            'books' => ['New on the shelf', 'Find your next favourite.', 'Stories, ideas and the stationery to go with them.', 'Browse books'],
        ];
    }

    /**
     * What every hero shares: its box, type and button, all taken from the theme variables.
     */
    protected function base(): string
    {
        return <<<'CSS'
.vh{position:relative;display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,.9fr);align-items:center;gap:2rem;min-height:30rem;padding:4rem 3.75rem;overflow:hidden;background:var(--color-white);color:var(--color-black);font-family:var(--font-poppins)}
.vh-text{position:relative;z-index:1;max-width:36rem}
.vh-eyebrow{margin:0 0 1rem;font-size:.8125rem;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:var(--color-navyBlue)}
.vh-title{margin:0;font-family:var(--font-dmserif);font-size:clamp(2.25rem,5vw,4rem);line-height:1.05;font-weight:400}
.vh-line{margin:1.25rem 0 2rem;font-size:1.0625rem;line-height:1.6;max-width:30rem}
.vh-button{display:inline-block;padding:.9rem 2rem;border:2px solid var(--color-navyBlue);border-radius:var(--radius-md);background:var(--color-navyBlue);color:var(--color-white);font-weight:600;text-decoration:none}
.vh-button:hover{background:transparent;color:var(--color-navyBlue)}
.vh-art{position:relative;align-self:stretch;min-height:16rem}
.vh-art span{position:absolute;display:block}
@media (max-width:768px){.vh{grid-template-columns:1fr;min-height:0;padding:2.5rem 1.25rem}.vh-art{min-height:9rem;order:-1}}

CSS;
    }

    /**
     * The look of each hero: how its art is drawn and what it does to the shared box.
     *
     * @return array<string, string>
     */
    protected function styles(): array
    {
        return [
            // Editorial split: a tall tinted panel with an accent disc and a vertical caption.
            'apparel' => <<<'CSS'
.vh-apparel{background:var(--color-white)}
.vh-apparel .vh-art{background:var(--color-lightOrange)}
.vh-apparel .vh-art span:nth-child(1){right:12%;top:14%;width:46%;aspect-ratio:1;border-radius:50%;background:var(--color-navyBlue)}
.vh-apparel .vh-art span:nth-child(2){left:8%;bottom:10%;width:34%;height:2px;background:var(--color-black)}
.vh-apparel .vh-art span:nth-child(3){left:8%;top:10%;width:2px;height:30%;background:var(--color-black)}
.vh-apparel .vh-title{font-style:italic}
CSS,

            // Dark console: glow in the corner over a fine grid, with a glowing button.
            'electronics' => <<<'CSS'
.vh-electronics{background:radial-gradient(circle at 85% 15%,color-mix(in oklab,var(--color-navyBlue) 45%,transparent),transparent 55%),linear-gradient(var(--color-lightOrange) 1px,transparent 1px) 0 0/44px 44px,linear-gradient(90deg,var(--color-lightOrange) 1px,transparent 1px) 0 0/44px 44px,var(--color-white)}
.vh-electronics .vh-title{font-weight:700;letter-spacing:-.02em}
.vh-electronics .vh-button{box-shadow:0 0 28px color-mix(in oklab,var(--color-navyBlue) 60%,transparent)}
.vh-electronics .vh-art span{border:1px solid var(--color-navyBlue);border-radius:var(--radius-md)}
.vh-electronics .vh-art span:nth-child(1){right:6%;top:8%;width:64%;height:56%}
.vh-electronics .vh-art span:nth-child(2){right:20%;top:30%;width:46%;height:46%;background:color-mix(in oklab,var(--color-navyBlue) 18%,transparent)}
.vh-electronics .vh-art span:nth-child(3){right:34%;top:52%;width:30%;height:30%;background:var(--color-navyBlue)}
CSS,

            // Warm rounded plate with soft blobs.
            'food' => <<<'CSS'
.vh-food{background:var(--color-lightOrange);border-radius:0 0 var(--radius-lg) var(--radius-lg)}
.vh-food .vh-title{font-weight:800}
.vh-food .vh-button{border-radius:999px}
.vh-food .vh-art span{border-radius:50%}
.vh-food .vh-art span:nth-child(1){right:4%;top:6%;width:58%;aspect-ratio:1;background:var(--color-navyBlue)}
.vh-food .vh-art span:nth-child(2){left:6%;bottom:6%;width:34%;aspect-ratio:1;background:var(--color-white)}
.vh-food .vh-art span:nth-child(3){right:2%;bottom:4%;width:18%;aspect-ratio:1;background:color-mix(in oklab,var(--color-navyBlue) 35%,var(--color-white))}
CSS,

            // A calm arch on a quiet ground.
            'home' => <<<'CSS'
.vh-home{background:var(--color-white);border-bottom:1px solid var(--color-lightOrange)}
.vh-home .vh-art span:nth-child(1){right:10%;bottom:0;width:62%;height:92%;border-radius:999px 999px 0 0;background:var(--color-lightOrange)}
.vh-home .vh-art span:nth-child(2){right:24%;bottom:0;width:34%;height:58%;border-radius:999px 999px 0 0;background:var(--color-navyBlue)}
.vh-home .vh-art span:nth-child(3){left:2%;bottom:0;width:16%;height:22%;border-radius:var(--radius-md) var(--radius-md) 0 0;background:color-mix(in oklab,var(--color-navyBlue) 30%,var(--color-white))}
CSS,

            // Centred, with a soft circle of colour behind the words.
            'beauty' => <<<'CSS'
.vh-beauty{grid-template-columns:1fr;justify-items:center;text-align:center;background:radial-gradient(circle at 50% 40%,var(--color-lightOrange),var(--color-white) 70%)}
.vh-beauty .vh-text{max-width:40rem}
.vh-beauty .vh-line{margin-inline:auto}
.vh-beauty .vh-title{font-weight:300;letter-spacing:.01em}
.vh-beauty .vh-art{position:absolute;inset:0;min-height:0;order:0}
.vh-beauty .vh-art span{border-radius:50%;border:1px solid var(--color-navyBlue);opacity:.35}
.vh-beauty .vh-art span:nth-child(1){left:50%;top:50%;width:26rem;aspect-ratio:1;translate:-50% -50%}
.vh-beauty .vh-art span:nth-child(2){left:50%;top:50%;width:36rem;aspect-ratio:1;translate:-50% -50%;opacity:.2}
.vh-beauty .vh-art span:nth-child(3){display:none}
CSS,

            // Chunky toy-box shapes and rounded type.
            'kids' => <<<'CSS'
.vh-kids{background:var(--color-lightOrange)}
.vh-kids .vh-title{font-weight:700}
.vh-kids .vh-button{border-radius:999px;box-shadow:0 5px 0 color-mix(in oklab,var(--color-navyBlue) 55%,var(--color-black))}
.vh-kids .vh-art span:nth-child(1){left:6%;top:8%;width:42%;aspect-ratio:1;border-radius:50%;background:var(--color-navyBlue)}
.vh-kids .vh-art span:nth-child(2){right:6%;top:30%;width:36%;aspect-ratio:1;border-radius:var(--radius-lg);rotate:14deg;background:var(--color-white)}
.vh-kids .vh-art span:nth-child(3){left:28%;bottom:4%;width:30%;aspect-ratio:1;border-radius:var(--radius-md);rotate:-12deg;background:color-mix(in oklab,var(--color-navyBlue) 40%,var(--color-white))}
CSS,

            // A hard diagonal and heavy capitals.
            'sports' => <<<'CSS'
.vh-sports{background:var(--color-white)}
.vh-sports .vh-title{font-weight:900;text-transform:uppercase;letter-spacing:-.01em;line-height:.98}
.vh-sports .vh-eyebrow{background:var(--color-black);color:var(--color-white);display:inline-block;padding:.3rem .7rem}
.vh-sports .vh-art{margin:-4rem -3.75rem -4rem 0}
.vh-sports .vh-art span:nth-child(1){inset:0;background:var(--color-navyBlue);clip-path:polygon(28% 0,100% 0,100% 100%,0 100%)}
.vh-sports .vh-art span:nth-child(2){inset:0;background:var(--color-black);clip-path:polygon(18% 0,26% 0,0 100%,0 100%);opacity:.9}
.vh-sports .vh-art span:nth-child(3){display:none}
@media (max-width:768px){.vh-sports .vh-art{margin:-2.5rem -1.25rem 0}}
CSS,

            // Quiet and centred, with fine rules either side of the title.
            'jewelry' => <<<'CSS'
.vh-jewelry{grid-template-columns:1fr;justify-items:center;text-align:center;background:var(--color-white);min-height:26rem}
.vh-jewelry .vh-text{max-width:42rem}
.vh-jewelry .vh-eyebrow{letter-spacing:.32em}
.vh-jewelry .vh-title{font-weight:400;letter-spacing:.03em}
.vh-jewelry .vh-line{margin-inline:auto}
.vh-jewelry .vh-button{background:transparent;color:var(--color-navyBlue);letter-spacing:.14em;text-transform:uppercase;font-size:.8125rem}
.vh-jewelry .vh-button:hover{background:var(--color-navyBlue);color:var(--color-white)}
.vh-jewelry .vh-art{position:absolute;inset:0;min-height:0;order:0}
.vh-jewelry .vh-art span{left:50%;width:1px;background:var(--color-navyBlue);translate:-50% 0}
.vh-jewelry .vh-art span:nth-child(1){top:0;height:2.5rem}
.vh-jewelry .vh-art span:nth-child(2){bottom:0;height:2.5rem}
.vh-jewelry .vh-art span:nth-child(3){display:none}
CSS,

            // A row of book spines.
            'books' => <<<'CSS'
.vh-books{background:var(--color-white)}
.vh-books .vh-title{font-weight:600}
.vh-books .vh-art{display:flex;align-items:flex-end;gap:.5rem}
.vh-books .vh-art span{position:static;flex:1;border-radius:3px 3px 0 0;background:var(--color-navyBlue)}
.vh-books .vh-art span:nth-child(1){height:78%}
.vh-books .vh-art span:nth-child(2){height:100%;background:var(--color-lightOrange);border:2px solid var(--color-navyBlue)}
.vh-books .vh-art span:nth-child(3){height:64%;background:color-mix(in oklab,var(--color-navyBlue) 45%,var(--color-white))}
.vh-books .vh-art::after{content:"";position:absolute;inset:auto 0 0 0;height:3px;background:var(--color-black)}
CSS,
        ];
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class PrintLogoTest extends TestCase
{
    public function test_ecogreen_logo_asset_is_available_for_printouts(): void
    {
        $this->assertFileExists(public_path('images/ecogreen-logo-print.png'));
    }

    public function test_all_inspection_printouts_use_ecogreen_logo(): void
    {
        $printLogo = 'src="/images/ecogreen-logo-print.png" class="print-company-logo"';

        $this->get('/dashboard/fire-hydrants')
            ->assertOk()
            ->assertSee($printLogo, false)
            ->assertDontSee('class="print-logo"', false);

        $this->get('/dashboard/fire-extinguishers')
            ->assertOk()
            ->assertSee($printLogo, false)
            ->assertDontSee('class="print-logo"', false);

        $this->get('/dashboard/es-ew-inspections')
            ->assertOk()
            ->assertSee($printLogo, false)
            ->assertDontSee('class="print-logo"', false);
    }

    public function test_point_qr_printout_uses_ecogreen_logo(): void
    {
        $this->get('/dashboard/points')
            ->assertOk()
            ->assertSee('id="qrPrintLogo"', false)
            ->assertSee('src="/images/ecogreen-logo-print.png"', false)
            ->assertSee('class="qr-print-logo"', false);
    }
}

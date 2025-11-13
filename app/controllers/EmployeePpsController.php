<?php

require_once __DIR__ . '/EmployeeSiteBaseController.php';

/**
 * Class EmployeePpsController
 *
 * Controller untuk mengelola data karyawan di site PPS1.
 * Mewarisi fungsionalitas dari EmployeeSiteBaseController dan mengaturnya untuk site PPS1.
 */
class EmployeePpsController extends EmployeeSiteBaseController {
    /**
     * @var string Kode unik untuk site.
     */
    protected string $siteCode = 'PPS1';
    /**
     * @var string Label atau nama tampilan untuk site.
     */
    protected string $siteLabel = 'Karyawan PPS1';
    /**
     * @var string Path routing untuk controller.
     */
    protected string $routePath = 'EmployeePpsController';
}

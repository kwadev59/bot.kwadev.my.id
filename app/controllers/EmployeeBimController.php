<?php

require_once __DIR__ . '/EmployeeSiteBaseController.php';

/**
 * Class EmployeeBimController
 *
 * Controller untuk mengelola data karyawan di site BIM1.
 * Mewarisi fungsionalitas dari EmployeeSiteBaseController dan mengaturnya untuk site BIM1.
 */
class EmployeeBimController extends EmployeeSiteBaseController {
    /**
     * @var string Kode unik untuk site.
     */
    protected string $siteCode = 'BIM1';
    /**
     * @var string Label atau nama tampilan untuk site.
     */
    protected string $siteLabel = 'Karyawan BIM1';
    /**
     * @var string Path routing untuk controller.
     */
    protected string $routePath = 'EmployeeBimController';
}

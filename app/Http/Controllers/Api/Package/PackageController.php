<?php

namespace App\Http\Controllers\Api\Package;

use App\Http\Resources\V1\PackageResource;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class PackageController extends Controller
{
    public function __construct()
    {
        $this->setResourceClass('package');
    }

    public function index(Request $request)
    {
        return $this->resourceClass->packageListing($request);
    }

    public function store(Request $request)
    {
        return $this->resourceClass->insertPackage($request);
    }

    public function update(Request $request)
    {
        return $this->resourceClass->insertPackage($request);
    }

    public function show(Request $request, String $version, $packageId)
    {
        return $this->resourceClass->getPackage($request, $packageId);
    }

    public function getAssociateCompanies(Request $request, String $version, $packageId)
    {
        return $this->resourceClass->getAssociateCompanies($request, $packageId);
    }

    public function changePackageStatus(Request $request, String $version, $packageId, $status)
    {
        return $this->resourceClass->changePackageStatus($request, $packageId, $status);
    }
}

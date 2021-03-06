<?php
/*
 * WellCommerce Open-Source E-Commerce Platform
 *
 * This file is part of the WellCommerce package.
 *
 * (c) Adam Piotrowski <adam@wellcommerce.org>
 *
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 */

namespace WellCommerce\Bundle\SmugglerBundle\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use WellCommerce\Bundle\CoreBundle\Controller\Admin\AbstractAdminController;
use WellCommerce\Bundle\SmugglerBundle\Helper\Package\PackageHelperInterface;

/**
 * Class PackageController
 *
 * @author  Adam Piotrowski <adam@wellcommerce.org>
 */
class PackageController extends AbstractAdminController
{
    /**
     * @var \WellCommerce\Bundle\SmugglerBundle\Manager\Admin\PackageManager
     */
    protected $manager;

    /**
     * Action used to sync packages from remote servers ie. packagist.org
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function syncAction()
    {
        $this->manager->syncPackages(PackageHelperInterface::DEFAULT_PACKAGE_BUNDLE_TYPE);
        $this->manager->syncPackages(PackageHelperInterface::DEFAULT_PACKAGE_THEME_TYPE);
        $this->manager->getFlashHelper()->addSuccess('package.flashes.sync_success');

        return $this->getRouterHelper()->redirectToAction('index');
    }

    public function packageAction(Request $request, $operation)
    {
        $resource = $this->manager->findResource($request);
        if (null === $resource) {
            return $this->redirectToAction('index');
        }

        $form = $this->manager->getForm($resource);

        return $this->displayTemplate('package', [
            'operation'   => $operation,
            'packageName' => $resource->getFullName(),
            'form'        => $form,
        ]);
    }

    public function consoleAction(Request $request)
    {
        $helper    = $this->getHelper();
        $arguments = $this->manager->getConsoleCommandArguments($request);
        $process   = $helper->getProcess($arguments, 720);
        $process->run();

        if ($process->getExitCode() !== null) {
            if (0 === (int)$process->getExitCode()) {
                $this->manager->changePackageStatus($request);
            }

            return $this->jsonResponse(['code' => $process->getExitCode(), 'error' => $process->getErrorOutput()]);
        }
    }

    /**
     * @return \WellCommerce\Bundle\CoreBundle\Helper\Environment\EnvironmentHelperInterface
     */
    protected function getHelper()
    {
        return $this->get('environment_helper');
    }
}

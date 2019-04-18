<?php

namespace Comcast\PhpLegalLicenses\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends DependencyLicenseCommand
{
    /**
     * @var bool
     */
    private $hideVersion = false;

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('generate')
            ->setDescription('Generate Licenses file from project dependencies.')
            ->addOption('hide-version', 'hv', InputOption::VALUE_NONE, 'Hide dependency version');
    }

    /**
     * Execute the command.
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->hideVersion = $input->getOption('hide-version');
        $dependencies = $this->getDependencyList();

        $this->generateLicensesText($dependencies);
    }

    /**
     * Generates Licenses Text using packages retrieved from composer.lock file.
     *
     * @param array $dependencies
     *
     * @return void
     */
    protected function generateLicensesText($dependencies)
    {
        $text = [];

        foreach ($dependencies as $dependency) {
            $text[] = $this->getTextForDependency($dependency);
        }

        echo implode("\n", $text);
    }

    /**
     * Returns Boilerplate text for the Licences File.
     *
     * @return string
     */
    protected function getBoilerplate()
    {
        return '';
    }

    /**
     * Retrieves text containing version, sha, and license information for the specified dependency.
     *
     * @param array $dependency
     *
     * @return string
     */
    protected function getTextForDependency($dependency)
    {
        $name = $dependency['name'];
        $description = isset($dependency['description']) ? $dependency['description'] : 'Not configured.';
        $version = $dependency['version'];
        $homepage = isset($dependency['homepage']) ? $dependency['homepage'] : 'Not configured.';
        $sha = str_split($dependency['source']['reference'], 7)[0];
        $licenseNames = isset($dependency['license']) ? implode(', ', $dependency['license']) : 'Not configured.';
        $license = $this->getFullLicenseText($name, $dependency['source']['url']);

        return $this->generateDependencyText($name, $description, $version, $homepage, $sha, $licenseNames, $license);
    }

    /** Retrieves full license text for a dependency from the vendor directory.
     *
     * @param string $name
     *
     * @return string
     */
    protected function getFullLicenseText($name, $url)
    {
        $path = getcwd()."/vendor/$name/";
        $filenames = ['LICENSE.txt', 'LICENSE.md', 'LICENSE', 'license.txt', 'license.md', 'license', 'LICENSE-2.0.txt'];

        foreach ($filenames as $filename) {
            if (file_exists($path.$filename)) {
                return $this->getLicenseUrl($url, $filename);
            }
        }

        return 'Full license text not found in dependency source.';
    }

    protected function getLicenseUrl($url, $fileName)
    {
        if (substr($url, -4) === '.git') {
            $url = substr($url, 0, -4);
        }
        if (strpos($url, 'https://github.com/') === 0) {
            return $url.'/blob/master/'.$fileName;
        }
        return '';
    }

    /**
     * Generates Dependency Text based on boilerplate.
     *
     * @param string $name
     * @param string $description
     * @param string $version
     * @param string $homepage
     * @param string $sha
     * @param string $licenseNames
     * @param string $license
     *
     * @return string
     */
    protected function generateDependencyText($name, $description, $version, $homepage, $sha, $licenseNames, $license)
    {
        return "$name,".($this->hideVersion ? '' : "@$version").",$licenseNames,".($license ?: $homepage);
    }
}

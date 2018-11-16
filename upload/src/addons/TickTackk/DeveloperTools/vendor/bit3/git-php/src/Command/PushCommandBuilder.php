<?php

/**
 * This file is part of bit3/git-php.
 *
 * (c) Tristan Lins <tristan@lins.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    bit3/git-php
 * @author     Tristan Lins <tristan@lins.io>
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2014 Tristan Lins <tristan@lins.io>
 * @license    https://github.com/bit3/git-php/blob/master/LICENSE MIT
 * @link       https://github.com/bit3/git-php
 * @filesource
 */

namespace Bit3\GitPhp\Command;

/**
 * Push command builder.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PushCommandBuilder extends AbstractCommandBuilder
{
    const RECURSE_SUBMODULES_CHECK = 'check';

    const RECURSE_SUBMODULES_ON_DEMAND = 'on-demand';

    /**
     * {@inheritDoc}
     */
    protected function initializeProcessBuilder()
    {
        $this->processBuilder->add('push');
    }

    /**
     * Add the all option to the command line.
     *
     * @return PushCommandBuilder
     */
    public function all()
    {
        $this->processBuilder->add('--all');
        return $this;
    }

    /**
     * Add the prune option to the command line.
     *
     * @return PushCommandBuilder
     */
    public function prune()
    {
        $this->processBuilder->add('--prune');
        return $this;
    }

    /**
     * Add the mirror option to the command line.
     *
     * @return PushCommandBuilder
     */
    public function mirror()
    {
        $this->processBuilder->add('--mirror');
        return $this;
    }

    /**
     * Add the dry-run option to the command line.
     *
     * @return PushCommandBuilder
     */
    public function dryRun()
    {
        $this->processBuilder->add('--dry-run');
        return $this;
    }

    /**
     * Add the porcelain option to the command line.
     *
     * @return PushCommandBuilder
     */
    public function porcelain()
    {
        $this->processBuilder->add('--porcelain');
        return $this;
    }

    /**
     * Add the delete option to the command line.
     *
     * @return PushCommandBuilder
     */
    public function delete()
    {
        $this->processBuilder->add('--delete');
        return $this;
    }

    /**
     * Add the tags option to the command line.
     *
     * @return PushCommandBuilder
     */
    public function tags()
    {
        $this->processBuilder->add('--tags');
        return $this;
    }

    /**
     * Add the follow-tags option to the command line.
     *
     * @return PushCommandBuilder
     */
    public function followTags()
    {
        $this->processBuilder->add('--follow-tags');
        return $this;
    }

    /**
     * Add the receive-pack option to the command line.
     *
     * @param string $gitReceivePack The value.
     *
     * @return PushCommandBuilder
     */
    public function receivePack($gitReceivePack)
    {
        $this->processBuilder->add('--receive-pack=' . $gitReceivePack);
        return $this;
    }

    /**
     * Add the  option to the command line.
     *
     * @param null|string $refname The ref name.
     *
     * @param null        $expect  The expect value.
     *
     * @return PushCommandBuilder
     */
    public function forceWithLease($refname, $expect = null)
    {
        $this->processBuilder->add(
            '--force-with-lease' . ($refname ? ('=' . $refname . ($expect ? ':' . $expect : '')) : '')
        );
        return $this;
    }

    /**
     * Add the no-force-with-lease option to the command line.
     *
     * @return PushCommandBuilder
     */
    public function noForceWithLease()
    {
        $this->processBuilder->add('--no-force-with-lease');
        return $this;
    }

    /**
     * Add the force option to the command line.
     *
     * @return PushCommandBuilder
     */
    public function force()
    {
        $this->processBuilder->add('--force');
        return $this;
    }

    /**
     * Add the repo option to the command line.
     *
     * @param string $repository The repository name.
     *
     * @return PushCommandBuilder
     */
    public function repo($repository)
    {
        $this->processBuilder->add('--repo=' . $repository);
        return $this;
    }

    /**
     * Add the set-upstream option to the command line.
     *
     * @return PushCommandBuilder
     */
    public function setUpstream()
    {
        $this->processBuilder->add('--set-upstream');
        return $this;
    }

    /**
     * Add the thin option to the command line.
     *
     * @return PushCommandBuilder
     */
    public function thin()
    {
        $this->processBuilder->add('--thin');
        return $this;
    }

    /**
     * Add the no-thin option to the command line.
     *
     * @return PushCommandBuilder
     */
    public function noThin()
    {
        $this->processBuilder->add('--no-thin');
        return $this;
    }

    /**
     * Add the quiet option to the command line.
     *
     * @return PushCommandBuilder
     */
    public function quiet()
    {
        $this->processBuilder->add('--quiet');
        return $this;
    }

    /**
     * Add the verbose option to the command line.
     *
     * @return PushCommandBuilder
     */
    public function verbose()
    {
        $this->processBuilder->add('--verbose');
        return $this;
    }

    /**
     * Add the recurse-submodules option to the command line.
     *
     * @param string $recurse The value.
     *
     * @return PushCommandBuilder
     */
    public function recurseSubmodules($recurse)
    {
        $this->processBuilder->add('--recurse-submodules=' . $recurse);
        return $this;
    }

    /**
     * Add the verify option to the command line.
     *
     * @return PushCommandBuilder
     */
    public function verify()
    {
        $this->processBuilder->add('--verify');
        return $this;
    }

    /**
     * Add the no-verify option to the command line.
     *
     * @return PushCommandBuilder
     */
    public function noVerify()
    {
        $this->processBuilder->add('--no-verify');
        return $this;
    }

    /**
     * Build the command and execute it.
     *
     * @param string      $repository Name of the remote to push to.
     *
     * @param null|string $refspec    Ref spec to push.
     *
     * @param null|string $_          More optional ref specs to push.
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.ShortVariableName)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CamelCaseParameterName)
     */
    public function execute($repository, $refspec = null, $_ = null)
    {
        $this->processBuilder->add($repository);

        $refspecs = func_get_args();
        array_shift($refspecs);
        foreach ($refspecs as $refspec) {
            $this->processBuilder->add($refspec);
        }

        return parent::run();
    }
}

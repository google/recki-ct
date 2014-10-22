<?php
/**
 * Copyright 2014 Google Inc. All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @copyright 2014 Google Inc. All rights reserved
 * @license http://www.apache.org/licenses/LICENSE-2.0.txt Apache-2.0
 * @package Cli
 * @subpackage Command
 */

namespace ReckiCT_Cli\Command;

use ReckiCT\Jit;
use ReckiCT\Compiler\PECL\Compiler;

use Cilex\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompileCommand extends Command {

    protected function configure() {
        $this->setName('compile')
            ->setDescription('Compile a file or series of files')
            ->addOption('optimize', 'O', InputOption::VALUE_NONE, "Optimize the compile?", null)
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the module to compile')
            ->addArgument('files', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'The files to compile');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $jit = Jit::getInstance();
        $compiler = new Compiler;

        $ir = '';
        foreach ($input->getArgument('files') as $file) {
            $ir .= "\n" . $jit->getFileIr($file);
        }
        $name = $input->getArgument("name");
        $lname = strtolower($name);
        $files = $compiler->compile(trim($ir), $name);

        $dir = $this->makeTempDir();
        foreach ($files as $fname => $file) {
            file_put_contents($dir . '/' . $fname, $file);
        }

        $opt = $input->getOption("optimize") ? "-O3" : "";

        $command = "(cd $dir; phpize && CFLAGS=\"\$CFLAGS $opt\" ./configure && make)"; 
        passthru($command, $retval);
        if ($retval) {
            throw new \RuntimeException("There was an error: $retval");
        }
        rename("$dir/modules/$lname.so", getcwd() . "/$lname.so");
        echo "\nGenerated $lname.so\n";
    }

    protected function makeTempDir() {
        $randDir = 'recki-' . uniqid(mt_rand() . mt_rand());
        $dir = sys_get_temp_dir() . '/' . $randDir;
        mkdir($dir);
        if (!is_dir($dir)) {
            throw new \RuntimeException("Could not create temporary directory: $dir");
        }
        return $dir;
    }
}

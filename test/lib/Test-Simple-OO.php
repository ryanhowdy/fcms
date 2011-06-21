<?php
/*
    Test-Simple-OO.php:
        A workalike of Perl's Test::Simple for PHP.

    Why Test-Simple?
        Test-Simple is a super simple way to start testing RIGHT NOW.
        
    Why ok and not ok?
        Test-Simple produces TAP compliant output.
        For more on TAP, see: http://testanything.org
        For the TAP spec, see: http://search.cpan.org/dist/TAP/TAP.pm

    Why plan?
        Planning is enforced because, unless you explicitly declare your
        intent, the test set cannot ensure that all the required testing
        was performed. An assumption could be made, but error prone
        assumptions are exactly what testing is here to prevent.
    
    Other testing libraries:
        You can replace Test-Simple with Test-More without making any changes
        to existing test code, providing access to further testing methods.
        You can also replace any other PHP Test::More workalike library out there
        with Test-More.php and it will work without making any changes to the code.

    Assertions:
        produce TAP output
        provide basic testing functions (plan, ok)
        exit with error code:
            0                   all tests successful
            255                 test died or all passed but wrong # of tests run
            any other number    how many failed (including missing or extras)

    Example:
        require_once('Test-More-OO.php');
        $t = new TestMore();
        $t->plan(2);
        $t->ok(1 + 1 = 2, 'One plus one equals two');
        $t->ok( doSomethingAndReturnTrue() , 'doSomethingAndReturnTrue() successful');

    Procedural Example:
        require_once('Test-Simple');
        plan(2);
        ok(1 + 1 = 2, 'One plus one equals two');
        ok( doSomethingAndReturnTrue() , 'doSomethingAndReturnTrue() successful');

    From a browser
        If you are running Test-Simple on a web server and want slightly more web-readable
        output, call the web_output() method/function.

    Updates
        Updates will be posted to the Google code page:
        http://code.google.com/p/test-more-php/

    Bugs
        Please file bug reports via the Issues tracker at the google code page.

    Acknowledgements
        Michael G Schwern: http://search.cpan.org/~mschwern/Test-Simple/
        Chris Shiflet: http://shiflett.org/code/test-more.php

    Author
        Copyright RJ Herrick <RJHerrick@beyondlogical.net> 2009, 2010

*/

class TestSimple {

    protected $Results = array('Failed'=>NULL,'Passed'=>NULL);
    protected $TestName = array();
    protected $TestsRun = 0;
    protected $Skips;
    protected $NumberOfTests;
    protected $Filter;

    protected $notes;

    function plan ($NumberOfTests = NULL, $SkipReason = '') {
    // Get/set intended number of tests

        if ( is_int($this->NumberOfTests) && !is_null($NumberOfTests) ) $this->diag('The plan was already output.');

        if ( $NumberOfTests === 'no_plan' ) {
        // Equivalent to done_testing() at end of test script
            $this->NumberOfTests = $NumberOfTests;
            return;
        } else if ( $NumberOfTests === 'skip_all' ) {
        // Equivalent to done_testing() at end of test script
            $this->NumberOfTests = $NumberOfTests;
            $this->SkipAllReason = $SkipReason;
            $this->diag("Skipping all tests: $SkipReason");
            exit();
        }

        // Return current value if no params passed (query to the plan)
        if ( !func_num_args() && isset($this->NumberOfTests) ) return $this->NumberOfTests;

        // Number of tests looks acceptable
        if (!is_int($NumberOfTests) || 0 > $NumberOfTests) $this->bail( "Number of tests must be a positive integer. You gave it '$NumberOfTests'" );

        // If just reporting
        $skipinfo = '';
        if ($this->NumberOfTests === 'skip_all') $skipinfo = ' # '.$this->SkipAllReason;

        echo "1..${NumberOfTests}${skipinfo}\n";
        $this->NumberOfTests = $NumberOfTests;

        return;
    }

    function ok ($Result = NULL, $TestName = NULL) {
    // Confirm param 1 is true (in the PHP sense)
        // Unload the buffer regularly
        if ($this->Filter) {
            ob_flush();
        }

        $this->CurrentTestNumber++;
        $this->TestsRun++;

        if ($this->Skips) {
            $this->Skips--;
            $this->TestsSkipped++;
            echo('ok '.$this->CurrentTestNumber.' # skip '.$this->SkipReason."\n");
            return TRUE;
        }

        if ($this->NumberOfTests === 'skip_all') {
            $this->TestsSkipped++;
            $this->diag("SKIP '$TestName'");
            echo('ok '.$this->CurrentTestNumber." # skip\n");
            return TRUE;
        }

        if ( func_num_args() == 0 ) $this->bail('You must pass ok() a result to evaluate.');
        if ( func_num_args() == 2 ) $this->TestName[$this->CurrentTestNumber] = $TestName;
        if ( func_num_args() >  2 ) $this->bail('Wrong number of arguments passed to ok()');

        $verdict = $Result ? 'Passed' : 'Failed';

        $this->Results[$verdict]++;
        #$this->TestResult[$this->CurrentTestNumber] = $verdict;

        $caption = isset($this->TestName[$this->CurrentTestNumber]) ?  $this->TestName[$this->CurrentTestNumber] : '';

        $title = $this->CurrentTestNumber
                 . (isset($this->TestName[$this->CurrentTestNumber]) ? (' - '.$this->TestName[$this->CurrentTestNumber]) : '');

        if ($verdict === 'Passed') {
            echo "ok $title\n";
            return TRUE;

        } else {
            echo $this->LastFail = "not ok $title\n";

            $stack = isset($this->Backtrace) ? $this->Backtrace : debug_backtrace();
            $call = array_pop($stack);
            $file = basename($call['file']);
            $line = $call['line'];
            unset($this->Backtrace);

            if ($caption) {
                $this->diag("  Failed test '$caption'","  at $file line $line.");
                $this->LastFail .= "#   Failed test '$caption'\n#   at $file line $line.";
            } else {
                $this->diag("  Failed test at $file line $line.");
                $this->LastFail .= "#   Failed test at $file line $line.";
            }

            return FALSE;
        }
    }

    function done_testing () {
    // Change of plans (if there was one in the first place)
        $this->plan((int)$this->TestsRun);
        exit();
    }

    function bail ($message = '') {
    // Problem running the program
        TestSimple::_bail($message);
    }

    static function _bail ($message = '') {
        echo "Bail out! $message\n";
        exit(255);
    }

    function diag() {
    // Print a diagnostic comment
        $diagnostics = func_get_args();
        $msg = '';
        foreach ($diagnostics as $line) $msg .= "# ".str_replace("\n","\n# ",$line)."\n";
        echo $msg;
        if ($this->Filter) ob_flush();
        return $msg;
    }

    function __destruct () {
    // Parting remarks and proper exit code

    #    if ($this->NumberOfTests === 'no_plan') done_testing();
    #    if ($this->NumberOfTests === 'skip_all') plan(0);
    
        if ($this->TestsRun && !isset($this->NumberOfTests)) {
            echo "# Tests were run but no plan() was declared and done_testing() was not seen.\n";
        } else {
            if ($this->TestsRun !== $this->NumberOfTests) echo("# Looks like you planned ".(int)$this->NumberOfTests .' tests but ran '.(int)$this->TestsRun.".\n");
    
            if ($this->Results['Failed']) echo("# Looks like you failed ".  $this->Results['Failed'] .' tests of '.(int)$this->TestsRun.".\n");
        }

        // an extension to help debug
        if ($this->notes) echo $this->notes;

        if ($this->Filter) ob_end_flush();

        $retval = ($this->Results['Failed'] > 254) ? 254 : $this->Results['Failed'];
        exit($retval);
    }

    function web_output($callback = NULL) {
    // Basic web formatting (newlines) of output via ob filter
        if (isset($callback)) $this->Filter = $callback;
        if (!isset($this->Filter)) $this->Filter = create_function('$string','$output = str_replace("\n","<br />\n",$string); return $output;');
        ob_start($this->Filter);
    }

}

?>

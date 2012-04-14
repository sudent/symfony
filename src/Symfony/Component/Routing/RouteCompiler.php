<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing;

/**
 * RouteCompiler compiles Route instances to CompiledRoute instances.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class RouteCompiler implements RouteCompilerInterface
{
    const REGEX_DELIMITER = '#';

    /**
     * Compiles the current route instance.
     *
     * @param Route $route A Route instance
     *
     * @return CompiledRoute A CompiledRoute instance
     */
    public function compile(Route $route)
    {
        $staticPrefix = null;
        $hostnameVariables = array();
        $pathVariables = array();
        $variables = array();
        $tokens = array();
        $regex = null;
        $hostnameRegex = null;
        $hostnameTokens = array();

        if (null !== $hostnamePattern = $route->getHostnamePattern()) {

            $result = $this->compilePattern($route, $hostnamePattern, false);

            $hostnameVariables = $result['variables'];
            $variables = array_merge($variables, $hostnameVariables);

            $hostnameTokens = $result['tokens'];
            $hostnameRegex = $result['regex'];
        }

        $pattern = $route->getPattern();
        $result = $this->compilePattern($route, $pattern, true);

        $staticPrefix = $result['staticPrefix'];

        $pathVariables = $result['variables'];
        $variables = array_merge($variables, $pathVariables);

        $tokens = $result['tokens'];
        $regex = $result['regex'];

        return new CompiledRoute(
            $route,
            $staticPrefix,
            $regex,
            $tokens,
            array_unique($variables),
            $pathVariables,
            $hostnameVariables,
            $hostnameRegex,
            $hostnameTokens
        );
    }

    private function compilePattern(Route $route, $pattern, $isPath)
    {
        $len = strlen($pattern);
        $tokens = array();
        $variables = array();
        $pos = 0;

        if ($isPath) {
            $re = '#(?P<char>.)\{(?P<var>[\w\d_]+)\}#';
        } else {
            $re = '#(?P<char>^|.)\{(?P<var>[\w\d_]+)\}#';
        }

        preg_match_all($re, $pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        foreach ($matches as $match) {
            if ($text = substr($pattern, $pos, $match[0][1] - $pos)) {
                $tokens[] = array('text', $text);
            }
            if ($isPath) {
                $seps = array($pattern[$pos]);
            } else {
                $seps = array('.');
            }
            $pos = $match[0][1] + strlen($match[0][0]);
            $var = $match['var'][0];

            if ($req = $route->getRequirement($var)) {
                $regexp = $req;
            } else {
                if ($pos !== $len) {
                    $seps[] = $pattern[$pos];
                }
                $regexp = sprintf('[^%s]+?', preg_quote(implode('', array_unique($seps)), self::REGEX_DELIMITER));
            }

            $tokens[] = array('variable', $match['char'][0], $regexp, $var);

            if (in_array($var, $variables)) {
                throw new \LogicException(sprintf('Route pattern "%s" cannot reference variable name "%s" more than once.', $route->getPattern(), $var));
            }

            $variables[] = $var;
        }

        if ($pos < $len) {
            $tokens[] = array('text', substr($pattern, $pos));
        }

        // find the first optional token
        $firstOptional = INF;
        if ($isPath) {
            for ($i = count($tokens) - 1; $i >= 0; $i--) {
                $token = $tokens[$i];
                if ('variable' === $token[0] && $route->hasDefault($token[3])) {
                    $firstOptional = $i;
                } else {
                    break;
                }
            }
        }

        // compute the matching regexp
        $regexp = '';
        for ($i = 0, $nbToken = count($tokens); $i < $nbToken; $i++) {
            $regexp .= $this->computeRegexp($tokens, $i, $firstOptional);
        }

        return array(
            'staticPrefix' => 'text' === $tokens[0][0] ? $tokens[0][1] : '',
            'regex' => self::REGEX_DELIMITER.'^'.$regexp.'$'.self::REGEX_DELIMITER.'s',
            'tokens' => array_reverse($tokens),
            'variables' => $variables,
        );
    }

    /**
     * Computes the regexp used to match a specific token. It can be static text or a subpattern.
     *
     * @param array   $tokens        The route tokens
     * @param integer $index         The index of the current token
     * @param integer $firstOptional The index of the first optional token
     *
     * @return string The regexp pattern for a single token
     */
    private function computeRegexp(array $tokens, $index, $firstOptional)
    {
        $token = $tokens[$index];
        if('text' === $token[0]) {
            // Text tokens
            return preg_quote($token[1], self::REGEX_DELIMITER);
        } else {
            // Variable tokens
            if (0 === $index && 0 === $firstOptional && 1 == count($tokens)) {
                // When the only token is an optional variable token, the separator is required
                return sprintf('%s(?<%s>%s)?', preg_quote($token[1], self::REGEX_DELIMITER), $token[3], $token[2]);
            } else {
                $regexp = sprintf('%s(?<%s>%s)', preg_quote($token[1], self::REGEX_DELIMITER), $token[3], $token[2]);
                if ($index >= $firstOptional) {
                    // Enclose each optional token in a subpattern to make it optional.
                    // "?:" means it is non-capturing, i.e. the portion of the subject string that
                    // matched the optional subpattern is not passed back.
                    $regexp = "(?:$regexp";
                    $nbTokens = count($tokens);
                    if ($nbTokens - 1 == $index) {
                        // Close the optional subpatterns
                        $regexp .= str_repeat(")?", $nbTokens - $firstOptional);
                    }
                }
                return $regexp;
            }
        }
    }
}

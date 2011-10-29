<?php

namespace Symfony\Component\Security\Core\Authorization\Expression;

use Symfony\Component\Security\Core\Authorization\Expression\Ast\IsEqualExpression;

use Symfony\Component\Security\Core\Authorization\Expression\Ast\ParameterExpression;

use Symfony\Component\Security\Core\Authorization\Expression\Ast\VariableExpression;

use Symfony\Component\Security\Core\Authorization\Expression\Ast\ConstantExpression;
use Symfony\Component\Security\Core\Authorization\Expression\Ast\OrExpression;
use Symfony\Component\Security\Core\Authorization\Expression\Ast\AndExpression;
use Symfony\Component\Security\Core\Authorization\Expression\Ast\ArrayExpression;
use Symfony\Component\Security\Core\Authorization\Expression\Ast\GetItemExpression;
use Symfony\Component\Security\Core\Authorization\Expression\Ast\GetPropertyExpression;
use Symfony\Component\Security\Core\Authorization\Expression\Ast\MethodCallExpression;
use Symfony\Component\Security\Core\Authorization\Expression\Ast\ExpressionInterface;
use Symfony\Component\Security\Core\Authorization\Expression\Ast\FunctionExpression;

final class ExpressionParser
{
    const PRECEDENCE_OR       = 10;
    const PRECEDENCE_AND      = 15;
    const PRECEDENCE_IS_EQUAL = 20;

    private $lexer;

    public function __construct()
    {
        $this->lexer = new ExpressionLexer();
    }

    public function parse($str)
    {
        $this->lexer->initialize($str);

        return $this->Expression();
    }

    private function Expression($precedence = 0)
    {
        $expr = $this->Primary();

        while (true) {
            if (ExpressionLexer::T_AND === $this->lexer->lookahead['type']
                    && $precedence <= self::PRECEDENCE_AND) {
                $this->lexer->next();

                $expr = new AndExpression($expr, $this->Expression(
                    self::PRECEDENCE_AND + 1));
                continue;
            }

            if (ExpressionLexer::T_OR === $this->lexer->lookahead['type']
                    && $precedence <= self::PRECEDENCE_OR) {
                $this->lexer->next();

                $expr = new OrExpression($expr, $this->Expression(
                    self::PRECEDENCE_OR + 1));
                continue;
            }

            if (ExpressionLexer::T_IS_EQUAL === $this->lexer->lookahead['type']
                    && $precedence <= self::PRECEDENCE_IS_EQUAL) {
                $this->lexer->next();

                $expr = new IsEqualExpression($expr, $this->Expression(
                    self::PRECEDENCE_IS_EQUAL + 1));
                continue;
            }

            break;
        }

        return $expr;
    }

    private function Primary()
    {
        if (ExpressionLexer::T_OPEN_PARENTHESIS === $this->lexer->lookahead['type']) {
            $this->lexer->next();
            $expr = $this->Expression();
            $this->match(ExpressionLexer::T_CLOSE_PARENTHESIS);

            return $this->Suffix($expr);
        }

        if (ExpressionLexer::T_STRING === $this->lexer->lookahead['type']) {
            return new ConstantExpression($this->match(ExpressionLexer::T_STRING));
        }

        if (ExpressionLexer::T_OPEN_BRACE === $this->lexer->lookahead['type']) {
            return $this->Suffix($this->MapExpr());
        }

        if (ExpressionLexer::T_OPEN_BRACKET === $this->lexer->lookahead['type']) {
            return $this->Suffix($this->ListExpr());
        }

        if (ExpressionLexer::T_IDENTIFIER === $this->lexer->lookahead['type']) {
            $name = $this->match(ExpressionLexer::T_IDENTIFIER);

            if (ExpressionLexer::T_OPEN_PARENTHESIS === $this->lexer->lookahead['type']) {
                $args = $this->Arguments();

                return $this->Suffix(new FunctionExpression($name, $args));
            }

            return $this->Suffix(new VariableExpression($name));
        }

        if (ExpressionLexer::T_PARAMETER === $this->lexer->lookahead['type']) {
            return $this->Suffix(new ParameterExpression($this->match(ExpressionLexer::T_PARAMETER)));
        }

        $this->error('primary expression');
    }

    private function ListExpr()
    {
        $this->match(ExpressionLexer::T_OPEN_BRACKET);

        $elements = array();
        while (ExpressionLexer::T_CLOSE_BRACKET !== $this->lexer->lookahead['type']) {
            $elements[] = $this->Expression();

            if (ExpressionLexer::T_COMMA !== $this->lexer->lookahead['type']) {
                break;
            }
            $this->lexer->next();
        }

        $this->match(ExpressionLexer::T_CLOSE_BRACKET);

        return new ArrayExpression($elements);
    }

    private function MapExpr()
    {
        $this->match(ExpressionLexer::T_OPEN_BRACE);

        $entries = array();
        while (ExpressionLexer::T_CLOSE_BRACE !== $this->lexer->lookahead['type']) {
            $key = $this->match(ExpressionLexer::T_STRING);
            $this->match(ExpressionLexer::T_COLON);
            $entries[$key] = $this->Expression();

            if (ExpressionLexer::T_COMMA !== $this->lexer->lookahead['type']) {
                break;
            }

            $this->lexer->next();
        }

        $this->match(ExpressionLexer::T_CLOSE_BRACE);

        return new ArrayExpression($entries);
    }

    private function Suffix(ExpressionInterface $expr)
    {
        while (true) {
            if (ExpressionLexer::T_OBJECT_OPERATOR === $this->lexer->lookahead['type']) {
                $this->lexer->next();
                $name = $this->match(ExpressionLexer::T_IDENTIFIER);

                if (ExpressionLexer::T_OPEN_PARENTHESIS === $this->lexer->lookahead['type']) {
                    $args = $this->Arguments();
                    $expr = new MethodCallExpression($expr, $name, $args);
                    continue;
                }

                $expr = new GetPropertyExpression($expr, $name);
                continue;
            }

            if (ExpressionLexer::T_OPEN_BRACKET === $this->lexer->lookahead['type']) {
                $this->lexer->next();
                $key = $this->Expression();
                $this->match(ExpressionLexer::T_CLOSE_BRACKET);
                $expr = new GetItemExpression($expr, $key);
                continue;
            }

            break;
        }

        return $expr;
    }

    private function FunctionCall()
    {
        $name = $this->match(ExpressionLexer::T_IDENTIFIER);
        $args = $this->Arguments();

        return new FunctionExpression($name, $args);
    }

    private function Arguments()
    {
        $this->match(ExpressionLexer::T_OPEN_PARENTHESIS);
        $args = array();

        while (ExpressionLexer::T_CLOSE_PARENTHESIS !== $this->lexer->lookahead['type']) {
            $args[] = $this->Expression();

            if (ExpressionLexer::T_COMMA !== $this->lexer->lookahead['type']) {
                break;
            }

            $this->match(ExpressionLexer::T_COMMA);
        }
        $this->match(ExpressionLexer::T_CLOSE_PARENTHESIS);

        return $args;
    }

    private function Value()
    {
        return $this->matchAny(array(ExpressionLexer::T_STRING));
    }

    private function matchAny(array $types)
    {
        if (null !== $this->lexer->lookahead) {
            foreach ($types as $type) {
                if ($type === $this->lexer->lookahead['type']) {
                    $this->lexer->next();

                    return $this->lexer->token['value'];
                }
            }
        }

        $this->error(sprintf('one of these tokens "%s"',
            implode('", "', array_map(array('Symfony\Component\Security\Core\Authorization\Expression\Lexer', 'getLiteral'), $types))
        ));
    }

    private function match($type)
    {
        if (null === $this->lexer->lookahead
            || $type !== $this->lexer->lookahead['type']) {
            $this->error(sprintf('token "%s"', ExpressionLexer::getLiteral($type)));
        }

        $this->lexer->next();

        return $this->lexer->token['value'];
    }

    private function error($expected)
    {
        $actual = null === $this->lexer->lookahead ? 'end of file'
            : sprintf('token "%s" with value "%s" at position %d',
            ExpressionLexer::getLiteral($this->lexer->lookahead['type']),
            $this->lexer->lookahead['value'],
            $this->lexer->lookahead['position']);

        throw new \RuntimeException(sprintf('Execpted %s, but got %s.', $expected, $actual));
    }
}
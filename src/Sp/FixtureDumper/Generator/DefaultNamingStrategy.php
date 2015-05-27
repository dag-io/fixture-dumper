<?php

/*
 * This file is part of the FixtureDumper library.
 *
 * (c) Martin Parsiegla <martin.parsiegla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sp\FixtureDumper\Generator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Exception;
use Sp\FixtureDumper\Util\ClassUtils;

/**
 * @author Martin Parsiegla <martin.parsiegla@gmail.com>
 */
class DefaultNamingStrategy implements NamingStrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function fixtureName(ClassMetadata $metadata)
    {
        return ClassUtils::getClassName($metadata->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function modelName($model, ClassMetadata $metadata)
    {
        $identifiers = $this->getIdentifierValues($model, $metadata);
        $className = strtolower(
            preg_replace(
                '/([A-Z])/',
                '_$1',
                lcfirst(ClassUtils::getClassName($metadata->getName()))
            )
        );

        return $className . implode('_', $identifiers);
    }

    private function getIdentifierValues($model, ClassMetadata $metadata)
    {
        $identifierValues = $metadata->getIdentifierValues($model);

        foreach ($identifierValues as $associationName => &$value) {
            if (is_object($value)) {
                $mapping = $metadata->getAssociationMapping(
                    $associationName
                );

                if (!isset($mapping['joinColumns'][0])) {
                    throw new Exception(
                        sprintf(
                            'There are no join columns for association "%s" in model "%s"',
                            $associationName,
                            get_class($model)
                        )
                    );
                }

                $referencedColumnName = $mapping['joinColumns'][0]['referencedColumnName'];
                $value = $value->{'get'.$referencedColumnName}();
            }
        }

        return $identifierValues;
    }
}

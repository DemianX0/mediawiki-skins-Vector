<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 1.35
 */

namespace Vector\FeatureManagement\Tests;

use Vector\FeatureManagement\FeatureManager;

/**
 * @group Vector
 * @group FeatureManagement
 * @coversDefaultClass \Vector\FeatureManagement\FeatureManager
 */
class FeatureManagerTest extends \MediaWikiUnitTestCase {

	/**
	 * @covers ::registerRequirement
	 */
	public function testRegisterRequirementThrowsWhenRequirementIsRegisteredTwice() {
		$this->expectException( \LogicException::class );

		$featureManager = new FeatureManager();
		$featureManager->registerRequirement( 'requirementA', true );
		$featureManager->registerRequirement( 'requirementA', true );
	}

	/**
	 * @covers ::registerRequirement
	 */
	public function testRegisterRequirementValidatesIsEnabled() {
		$this->expectException( \Wikimedia\Assert\ParameterAssertionException::class );

		$featureManager = new FeatureManager();
		$featureManager->registerRequirement( 'requirementA', 'foo' );
	}

	public static function provideInvalidFeatureConfig() {
		return [

			// ::registerFeature( string, int[] ) will throw an exception.
			[
				\Wikimedia\Assert\ParameterAssertionException::class,
				[ 1 ],
			],

			// The "bar" requirement hasn't been registered.
			[
				\InvalidArgumentException::class,
				[
					'bar',
				],
			],
		];
	}

	/**
	 * @dataProvider provideInvalidFeatureConfig
	 * @covers ::registerFeature
	 */
	public function testRegisterFeatureValidatesConfig( $expectedExceptionType, $config ) {
		$this->expectException( $expectedExceptionType );

		$featureManager = new FeatureManager();
		$featureManager->registerRequirement( 'requirement', true );
		$featureManager->registerFeature( 'feature', $config );
	}

	/**
	 * @covers ::isRequirementMet
	 */
	public function testIsRequirementMet() {
		$featureManager = new FeatureManager();
		$featureManager->registerRequirement( 'enabled', true );
		$featureManager->registerRequirement( 'disabled', false );

		$this->assertTrue( $featureManager->isRequirementMet( 'enabled' ) );
		$this->assertFalse( $featureManager->isRequirementMet( 'disabled' ) );
	}

	/**
	 * @covers ::isRequirementMet
	 */
	public function testIsRequirementMetThrowsExceptionWhenRequirementIsntRegistered() {
		$this->expectException( \InvalidArgumentException::class );

		$featureManager = new FeatureManager();
		$featureManager->isRequirementMet( 'foo' );
	}

	/**
	 * @covers ::registerFeature
	 */
	public function testRegisterFeatureThrowsExceptionWhenFeatureIsRegisteredTwice() {
		$this->expectException( \LogicException::class );

		$featureManager = new FeatureManager();
		$featureManager->registerFeature( 'featureA', [] );
		$featureManager->registerFeature( 'featureA', [] );
	}

	/**
	 * @covers ::isFeatureEnabled
	 */
	public function testIsFeatureEnabled() {
		$featureManager = new FeatureManager();
		$featureManager->registerRequirement( 'foo', false );
		$featureManager->registerFeature( 'requiresFoo', 'foo' );

		$this->assertFalse(
			$featureManager->isFeatureEnabled( 'requiresFoo' ),
			'A feature is disabled when the requirement that it requires is disabled.'
		);

		// ---

		$featureManager->registerRequirement( 'bar', true );
		$featureManager->registerRequirement( 'baz', true );

		$featureManager->registerFeature( 'requiresFooBar', [ 'foo', 'bar' ] );
		$featureManager->registerFeature( 'requiresBarBaz', [ 'bar', 'baz' ] );

		$this->assertFalse(
			$featureManager->isFeatureEnabled( 'requiresFooBar' ),
			'A feature is disabled when at least one requirement that it requires is disabled.'
		);

		$this->assertTrue( $featureManager->isFeatureEnabled( 'requiresBarBaz' ) );
	}

	/**
	 * @covers ::isFeatureEnabled
	 */
	public function testIsFeatureEnabledThrowsExceptionWhenFeatureIsntRegistered() {
		$this->expectException( \InvalidArgumentException::class );

		$featureManager = new FeatureManager();
		$featureManager->isFeatureEnabled( 'foo' );
	}
}

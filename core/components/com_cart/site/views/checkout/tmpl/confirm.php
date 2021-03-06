<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die('Restricted access');

/*$this->css()
     ->js('spin.min.js')
     ->js('autosubmit.js');*/

$this->css('jquery.ui.css', 'system');
?>

<header id="content-header">
	<h2>Payment confirmation</h2>
</header>

<?php
if ($this->paymentStatus == 'ok')
{
?>

<section class="main section">
	<div class="section-inner">
		<?php
		$errors = $this->getError();
		if (!empty($errors))
		{
			foreach ($errors as $error)
			{
				echo '<p class="error">' . $error . '</p>';
			}
		}
		?>

		<?php

		$perks = false;
		if (!empty($this->transactionInfo->tiPerks))
		{
			$perks = $this->transactionInfo->tiPerks;
			$perks = unserialize($perks);
		}

		$membershipInfo = false;
		if (!empty($this->transactionInfo->tiMeta))
		{
			$meta = unserialize($this->transactionInfo->tiMeta);

			if (!empty($meta['membershipInfo']))
			{
				$membershipInfo = $meta['membershipInfo'];
			}
		}

		$view = new \Hubzero\Component\View(array('name' => 'shared', 'layout' => 'messages'));
		$view->setError($this->getError());
		$view->display();
		?>

		<div class="grid">
			<?php
			$view = new \Hubzero\Component\View(array('name' => 'checkout', 'layout' => 'checkout_items'));
			$view->perks = $perks;
			$view->membershipInfo = $membershipInfo;
			$view->transactionItems = $this->transactionItems;
			$view->tiShippingDiscount = $this->transactionInfo->tiShippingDiscount;

			echo '<div class="col span6">';

			$view->display();

			echo '</div>';

			echo '<div class="col span6 omega orderSummary">';

			if (!empty($this->transactionInfo))
			{
				$orderTotal = $this->transactionInfo->tiSubtotal + $this->transactionInfo->tiShipping - $this->transactionInfo->tiDiscounts - $this->transactionInfo->tiShippingDiscount;
				$discount = $this->transactionInfo->tiDiscounts + $this->transactionInfo->tiShippingDiscount;

				echo '<h2>Order summary:</h2>';

				echo '<p>Order subtotal: ' . '$' . number_format($this->transactionInfo->tiSubtotal, 2) . '</p>';

				if ($this->transactionInfo->tiShipping > 0)
				{
					echo '<p>Shipping: ' . '$' . number_format($this->transactionInfo->tiShipping, 2) . '</p>';
				}
				if ($discount > 0)
				{
					echo '<p>Discounts: ' . '$' . number_format($discount, 2) . '</p>';
				}

				echo '<p class="orderTotal">Order total: ' . '$' . number_format($orderTotal, 2) . '</p>';
			}

			echo '</div>';
			?>
		</div>
		<?php

		// Check the notes, both SKU-specific and other
		$notes = array();
		foreach ($this->transactionItems as $item)
		{
			$meta = $item['transactionInfo']->tiMeta;
			if (isset($meta->checkoutNotes) && $meta->checkoutNotes)
			{
				$notes[] = array(
					'label' => $item['info']->pName . ', ' . $item['info']->sSku,
					'notes' => $meta->checkoutNotes
				);
			}
		}

		$genericNotesLabel = '';
		if (!empty($notes))
		{
			$genericNotesLabel = 'Other notes/comments';
		}

		if ($this->transactionInfo->tiNotes)
		{
			$notes[] = array(
				'label' => $genericNotesLabel,
				'notes' => $this->transactionInfo->tiNotes);
		}

		if (!empty($notes))
		{
			echo '<div class="section">';
			echo '<h2>Notes/Comments</h2>';
			foreach ($notes as $note)
			{
				echo '<p>';
				echo $note['label'];
				if ($note['label'])
				{
					echo ': ';
				}
				echo $note['notes'];
				echo '</p>';
			}
			echo '<a href="';
			echo Route::url('index.php?option=com_cart') . 'checkout?update=true';
			echo '">Change</a>';
			echo '</div>';
		};

		if (in_array('shipping', $this->transactionInfo->steps))
		{
			$view = new \Hubzero\Component\View(array('name' => 'checkout', 'layout' => 'checkout_shippinginfo'));
			$view->transactionInfo = $this->transactionInfo;
			$view->display();
		}

		$view = new \Hubzero\Component\View(array('name' => 'checkout', 'layout' => 'checkout_paymentinfo'));
		$view->paymentInfo = $this->paymentInfo;
		$view->display();

		?>
	</div>
</section>

<?php
}
?>

<section class="section">
	<div class="section-inner">
	<?php
	echo $this->paymentResponse;
	?>
	</div>
</section>
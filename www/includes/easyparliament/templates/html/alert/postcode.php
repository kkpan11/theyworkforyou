<div class="full-page alerts-header alerts-header--jumbo">
    <div class="full-page__row">
        <div class="full-page__unit">

            <h1>Track your <?php if ($data['recent_election']): ?>new<?php endif ?> MP&rsquo;s parliamentary activity</h1>

          <?php if (isset($data['confirmation_sent'])): ?>

            <div class="alerts-message alerts-message--confirmation-sent">
                <h2>Almost there!</h2>
                <p>We just need to check your email address.</p>
                <p>Please click the link in the email we just sent you.</p>
            </div>

          <?php elseif (isset($data['signedup_no_confirm']) || isset($data['confirmation_received'])): ?>

            <div class="alerts-message alerts-message--confirmation-received">
                <h2>Thanks for subscribing!</h2>
                <p>You will now receive alerts when your MP speaks or votes in Parliament, or receives an answer to a written question.</p>
                <?php if (isset($data['user_signed_in'])): ?>
                <p><a href="/alert/" class="button radius">Show my email settings</a></p>
                <?php endif ?>
            </div>

          <?php elseif (isset($data['error'])): ?>
            <div class="alerts-message alerts-message--error">
                <h2>Something went wrong</h2>
                <p>Sorry, we were unable to create this alert. Please <a href="mailto:<?= str_replace('@', '&#64;', CONTACTEMAIL) ?>">let us know</a>. Thanks.</p>
            </div>

          <?php else: ?>

            <p class="lead">Enter your postcode, and we&rsquo;ll email you every time your MP speaks, votes, or receives a written answer.</p>

          <?php if (isset($data['invalid-postcode-or-email'])): ?>
            <div class="alerts-message alerts-message--error">
                <h2>Oops!</h2>
                <p>Either we didn't recognize that postcode or your email address wasn't valid.</p>
            </div>
          <?php endif ?>

          <?php if (isset($data['bad-constituency'])): ?>
            <div class="alerts-message alerts-message--error">
                <h2>Oops!</h2>
                <p>We can't find an MP for that postcode.</p>
            </div>
          <?php endif ?>

          <?php if (isset($data['user_signed_in']) && isset($data['already_signed_up'])): ?>
            <div class="alerts-message alerts-message--reminder">
                <h2>You are already signed up</h2>
                <p>You are already receiving alerts when your MP<?= isset($data['mp_name']) ? ', ' . $data['mp_name'] . ',' : '' ?> speaks or votes in Parliament, or receives an answer to a written question.</p>
                <p><a href="/alert/" class="button radius">Show my email settings</a></p>
            </div>
           <?php endif ?>

            <form class="alerts-form" method="post">
                <input type="hidden" name="add-alert" value="1">
                <p>
                    <label for="id_postcode">Your postcode</label>
                  <?php if (isset($data['postcode'])): ?>
                    <input type="text" name="postcode" id="id_postcode" value="<?= _htmlentities($data['postcode']) ?>">
                  <?php else: ?>
                    <input type="text" name="postcode" id="id_postcode">
                  <?php endif ?>
                </p>
                <p>
                    <label for="id_email">Your email address</label>
                  <?php if (isset($data['email'])): ?>
                    <input type="text" name="email" id="id_email" value="<?= _htmlentities($data['email']) ?>">
                  <?php else: ?>
                    <input type="text" name="email" id="id_email">
                  <?php endif ?>
                </p>
                <p>
                    <button type="submit" class="button radius">Set up alerts</button>
                </p>
            </form>

          <?php endif ?>

        </div>
    </div>
    <p class="image-attribution">Image <a href="https://www.flickr.com/photos/uk_parliament/53754559691/in/datetaken/">© House of Commons</a></p>
</div>

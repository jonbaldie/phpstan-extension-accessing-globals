<?php
final class Issue133PaddedUser
{
    use Issue133Trait;
    use Issue133CapturesTrait;

    public function padding(): void
    {
        $unrelated = 'This method must not receive diagnostics from a trait.';
        $unrelated .= 'Changing its length must not affect scope membership.';
    }
}

final class Issue133SecondUser
{
    use Issue133Trait;
    use Issue133CapturesTrait;
}

import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

const accountTypes = [
    { value: 'student', label: 'Student', hint: 'Apply for education support' },
    { value: 'donor', label: 'Donor', hint: 'Fund students (needs Admin approval)' },
];

export default function Register() {
    return (
        <>
            <Head title="Create an account" />

            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <fieldset className="grid gap-2">
                            <legend className="text-sm font-medium">
                                I am registering as
                            </legend>
                            <div className="grid grid-cols-2 gap-3">
                                {accountTypes.map((type) => (
                                    <label
                                        key={type.value}
                                        className="flex cursor-pointer flex-col gap-1 rounded-lg border p-3 text-sm has-[:checked]:border-primary has-[:checked]:bg-accent"
                                    >
                                        <span className="flex items-center gap-2 font-medium">
                                            <input
                                                type="radio"
                                                name="account_type"
                                                value={type.value}
                                                defaultChecked={type.value === 'student'}
                                                className="accent-primary"
                                                tabIndex={1}
                                            />
                                            {type.label}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {type.hint}
                                        </span>
                                    </label>
                                ))}
                            </div>
                            <InputError message={errors.account_type} />
                        </fieldset>

                        <div className="grid gap-2">
                            <Label htmlFor="name">Full name</Label>
                            <Input
                                id="name"
                                type="text"
                                name="name"
                                required
                                autoFocus
                                tabIndex={2}
                                autoComplete="name"
                                placeholder="Full name"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                tabIndex={3}
                                autoComplete="email"
                                placeholder="email@example.com"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Password</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                tabIndex={4}
                                autoComplete="new-password"
                                placeholder="Password"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                Confirm password
                            </Label>
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                required
                                tabIndex={5}
                                autoComplete="new-password"
                                placeholder="Confirm password"
                            />
                            <InputError message={errors.password_confirmation} />
                        </div>

                        <div className="grid gap-2">
                            <div className="flex items-start space-x-3">
                                <Checkbox id="consent" name="consent" tabIndex={6} />
                                <Label htmlFor="consent" className="leading-snug">
                                    I consent to HCM Foundation using my information
                                    to assess applications and administer funding.
                                </Label>
                            </div>
                            <InputError message={errors.consent} />
                        </div>

                        <Button
                            type="submit"
                            className="mt-2 w-full"
                            tabIndex={7}
                            disabled={processing}
                            data-test="register-button"
                        >
                            {processing && <Spinner />}
                            Create account
                        </Button>

                        <div className="text-center text-sm text-muted-foreground">
                            Already have an account?{' '}
                            <TextLink href={login()} tabIndex={8}>
                                Log in
                            </TextLink>
                        </div>
                    </div>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Create an account',
    description: 'Students and donors can register here',
};

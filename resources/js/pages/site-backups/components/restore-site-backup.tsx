import { Backup } from '@/types/backup';
import { BackupFile } from '@/types/backup-file';
import { useForm } from '@inertiajs/react';
import { FormEvent, ReactNode, useState } from 'react';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { usePage } from '@inertiajs/react';

export default function RestoreSiteBackup({
  backup,
  file,
  onBackupRestored,
  children,
}: {
  backup: Backup;
  file: BackupFile;
  onBackupRestored?: () => void;
  children: ReactNode;
}) {
  const [open, setOpen] = useState(false);
  const page = usePage<{ server: any; site: any }>();

  const form = useForm({
    site: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(
      route('site-backup-files.restore', {
        server: page.props.server.id,
        site: page.props.site.id,
        backup: backup.id,
        backupFile: file.id,
      }),
      {
        onSuccess: () => {
          setOpen(false);
          if (onBackupRestored) {
            onBackupRestored();
          }
        },
      },
    );
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Restore Site Backup</DialogTitle>
        <DialogDescription>
          This backup will be restored to the current site. This will overwrite the existing site files.
        </DialogDescription>
        </DialogHeader>
        <Form id="restore-site-backup-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label>Restore to current site</Label>
              <p className="text-sm text-gray-600 dark:text-gray-400">
                This backup will be restored to: <strong>{backup.site?.domain}</strong>
              </p>
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <div className="flex items-center gap-2">
            <Button form="restore-site-backup-form" type="button" onClick={submit} disabled={form.processing}>
              {form.processing && <LoaderCircleIcon className="animate-spin" />}
              Restore
            </Button>
            <DialogClose asChild>
              <Button variant="outline">Cancel</Button>
            </DialogClose>
          </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

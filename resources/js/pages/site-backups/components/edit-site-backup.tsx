import React, { FormEvent, ReactNode, useState, useEffect } from 'react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { LoaderCircle, X } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import { SharedData } from '@/types';
import { Input } from '@/components/ui/input';
import { Backup } from '@/types/backup';
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

export default function EditSiteBackup({ backup, children }: { backup: Backup; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const page = usePage<SharedData & { server: any; site: any }>();
  const [exclusions, setExclusions] = useState<string[]>([]);
  const [exclusionInput, setExclusionInput] = useState('');

  const form = useForm<{
    interval: string;
    custom_interval: string;
    keep: string;
  }>({
    interval: page.props.configs.cronjob_intervals[backup.interval] ? backup.interval : 'custom',
    custom_interval: backup.interval,
    keep: backup.keep_backups.toString(),
  });

  useEffect(() => {
    if (backup.exclusions) {
      setExclusions(backup.exclusions);
    }
  }, [backup.exclusions]);

  const addExclusion = () => {
    if (exclusionInput.trim() && !exclusions.includes(exclusionInput.trim())) {
      setExclusions([...exclusions, exclusionInput.trim()]);
      setExclusionInput('');
    }
  };

  const removeExclusion = (exclusion: string) => {
    setExclusions(exclusions.filter(e => e !== exclusion));
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(route('site-backups.update', { server: page.props.server.id, site: page.props.site.id, backup: backup.id }), {
      data: {
        ...form.data,
        exclusions,
      },
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Edit Site Backup</DialogTitle>
          <DialogDescription className="sr-only">Edit site backup</DialogDescription>
        </DialogHeader>
        <Form id="edit-site-backup-form" onSubmit={submit} className="p-4">
          <FormFields>
            {/*interval*/}
            <FormField>
              <Label htmlFor="interval">Interval</Label>
              <Select value={form.data.interval} onValueChange={(value) => form.setData('interval', value)}>
                <SelectTrigger id="interval">
                  <SelectValue placeholder="Select an interval" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    {Object.entries(page.props.configs.cronjob_intervals).map(([key, value]) => (
                      <SelectItem key={`interval-${key}`} value={key}>
                        {value}
                      </SelectItem>
                    ))}
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.interval} />
            </FormField>

            {/*custom interval*/}
            {form.data.interval === 'custom' && (
              <FormField>
                <Label htmlFor="custom_interval">Custom interval (crontab)</Label>
                <Input
                  id="custom_interval"
                  name="custom_interval"
                  value={form.data.custom_interval}
                  onChange={(e) => form.setData('custom_interval', e.target.value)}
                  placeholder="* * * * *"
                />
                <InputError message={form.errors.custom_interval} />
              </FormField>
            )}

            {/*backups to keep*/}
            <FormField>
              <Label htmlFor="keep">Backups to keep</Label>
              <Input id="keep" name="keep" value={form.data.keep} onChange={(e) => form.setData('keep', e.target.value)} />
              <InputError message={form.errors.keep} />
            </FormField>

            {/*exclusions*/}
            <FormField>
              <Label htmlFor="exclusions">File/Folder Exclusions</Label>
              <div className="space-y-2">
                <div className="flex gap-2">
                  <Input
                    id="exclusions"
                    name="exclusions"
                    value={exclusionInput}
                    onChange={(e) => setExclusionInput(e.target.value)}
                    placeholder="Enter exclusion pattern..."
                    onKeyPress={(e) => {
                      if (e.key === 'Enter') {
                        e.preventDefault();
                        addExclusion();
                      }
                    }}
                  />
                  <Button type="button" onClick={addExclusion} disabled={!exclusionInput.trim()}>
                    Add
                  </Button>
                </div>
                {exclusions.length > 0 && (
                  <div className="flex flex-wrap gap-2">
                    {exclusions.map((exclusion, index) => (
                      <div
                        key={index}
                        className="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded-md text-sm"
                      >
                        <span>{exclusion}</span>
                        <button
                          type="button"
                          onClick={() => removeExclusion(exclusion)}
                          className="hover:text-red-500"
                        >
                          <X className="h-3 w-3" />
                        </button>
                      </div>
                    ))}
                  </div>
                )}
              </div>
              <InputError message={form.errors.exclusions} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <div className="flex items-center gap-2">
            <Button form="edit-site-backup-form" type="button" onClick={submit} disabled={form.processing}>
              {form.processing && <LoaderCircle className="animate-spin" />}
              Update
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

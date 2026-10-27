import React, { useState } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import STKPush from "./STKPush";
import STKStatus from "./STKStatus";

export default function MpesaIndex({ auth }) {
    const [tab, setTab] = useState("pay"); // "pay" | "verify"
    const [checkoutId, setCheckoutId] = useState(null);

    // After a successful STK push, auto-switch to verify tab with the checkout ID
    const handlePaymentInitiated = (id) => {
        setCheckoutId(id);
        setTab("verify");
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    M-Pesa Payments
                </h2>
            }
        >
            <Head title="M-Pesa Payments" />

            <div className="py-12">
                <div className="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Tab switcher */}
                    <div className="flex border-b border-gray-200 dark:border-gray-700">
                        <button
                            onClick={() => setTab("pay")}
                            className={`px-5 py-2 text-sm font-medium transition border-b-2 -mb-px ${
                                tab === "pay"
                                    ? "border-green-600 text-green-600"
                                    : "border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                            }`}
                        >
                            Pay
                        </button>
                        <button
                            onClick={() => setTab("verify")}
                            className={`px-5 py-2 text-sm font-medium transition border-b-2 -mb-px ${
                                tab === "verify"
                                    ? "border-blue-600 text-blue-600"
                                    : "border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                            }`}
                        >
                            Verify Payment
                        </button>
                    </div>

                    {/* Panel */}
                    <div className="flex justify-center">
                        {tab === "pay" ? (
                            <STKPush onSuccess={handlePaymentInitiated} />
                        ) : (
                            <STKStatus checkoutRequestId={checkoutId} />
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
